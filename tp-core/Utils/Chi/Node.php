<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

namespace TP\Utils\Chi;

class Node
{
    public bool $rex = false;           // Is this a regex route?
    public array $endpoints = [];       // HTTP handler endpoints on the leaf node [methodType => Endpoint]
    public string $prefix = '';         // prefix is the common prefix we ignore
    public array $children = [];        // child nodes should be stored in-order for iteration, in groups of the node type. [nodeType => Node[]]
    public int $tail = 0;               // first byte of the child prefix (ASCII value)
    public int $typ = NodeType::STATIC; // node type: STATIC, REGEXP, PARAM, CATCHALL
    public int $label = 0;              // first byte of the prefix (ASCII value)

    /**
     * Get the endpoint for the specified method, creating it if it doesn't exist.
     */
    public function getEndpoint(int $method): Endpoint
    {
        if (!isset($this->endpoints[$method])) {
            $this->endpoints[$method] = new Endpoint();
        }

        return $this->endpoints[$method];
    }

    /**
     * Inserts a new route into the routing tree.
     */
    public function insertRoute(int $method, string $pattern, callable $handler): self
    {
        $search = $pattern;
        $n = $this;

        while (true) {
            if ('' === $search) {
                $n->setEndpoint($method, $handler, $pattern);

                return $n;
            }

            $label = ord($search[0]);
            $segTail = 0;
            $segEndIdx = 0;
            $segTyp = NodeType::STATIC;
            $segRexpat = '';

            if ('{' === $search[0] || '*' === $search[0]) {
                [$segTyp, $key, $segRexpat, $segTail, $segStartIdx, $segEndIdx] = self::patNextSegment($search);
            }

            $prefix = '';
            if (NodeType::REGEXP === $segTyp) {
                $prefix = $segRexpat;
            }

            $parent = $n;
            $n = $n->getEdge($segTyp, $label, $segTail, $prefix);

            if (null === $n) {
                $child = new self();
                $child->label = $label;
                $child->tail = $segTail;
                $child->prefix = $search;
                $hn = $parent->addChild($child, $search);
                $hn->setEndpoint($method, $handler, $pattern);

                return $hn;
            }

            if ($n->typ > NodeType::STATIC) {
                $search = substr($search, $segEndIdx);
                continue;
            }

            $commonPrefix = self::longestPrefix($search, $n->prefix);
            if ($commonPrefix === strlen($n->prefix)) {
                $search = substr($search, $commonPrefix);
                continue;
            }

            $child = new self();
            $child->typ = NodeType::STATIC;
            $child->prefix = substr($search, 0, $commonPrefix);
            $parent->replaceChild($search[0], $segTail, $child);

            $n->label = ord($n->prefix[$commonPrefix]);
            $n->prefix = substr($n->prefix, $commonPrefix);
            $child->addChild($n, $n->prefix);

            $search = substr($search, $commonPrefix);
            if ('' === $search) {
                $child->setEndpoint($method, $handler, $pattern);

                return $child;
            }

            $subchild = new self();
            $subchild->typ = NodeType::STATIC;
            $subchild->label = ord($search[0]);
            $subchild->prefix = $search;
            $hn = $child->addChild($subchild, $search);
            $hn->setEndpoint($method, $handler, $pattern);

            return $hn;
        }
    }

    /**
     * Add a child node to the current node.
     */
    public function addChild(self $child, string $prefix): self
    {
        $search = $prefix;

        $hn = $child;

        [$segTyp, $key, $segRexpat, $segTail, $segStartIdx, $segEndIdx] = self::patNextSegment($search);

        switch ($segTyp) {
            case NodeType::STATIC:
                break;

            default:
                if (NodeType::REGEXP === $segTyp) {
                    $child->prefix = $segRexpat;
                    $child->rex = true;
                }

                if (0 === $segStartIdx) {
                    $child->typ = $segTyp;

                    if (NodeType::CATCHALL === $segTyp) {
                        $segStartIdx = -1;
                    } else {
                        $segStartIdx = $segEndIdx;
                    }

                    if ($segStartIdx < 0) {
                        $segStartIdx = strlen($search);
                    }

                    $child->tail = $segTail;

                    if ($segStartIdx !== strlen($search)) {
                        $search = substr($search, $segStartIdx);

                        $nn = new self();
                        $nn->typ = NodeType::STATIC;
                        $nn->label = ord($search[0]);
                        $nn->prefix = $search;
                        $hn = $child->addChild($nn, $search);
                    }
                } elseif ($segStartIdx > 0) {
                    $child->typ = NodeType::STATIC;
                    $child->prefix = substr($search, 0, $segStartIdx);
                    $child->rex = false;

                    $search = substr($search, $segStartIdx);

                    $nn = new self();
                    $nn->typ = $segTyp;
                    $nn->label = ord($search[0]);
                    $nn->tail = $segTail;
                    $hn = $child->addChild($nn, $search);
                }
                break;
        }

        if (!isset($this->children[$child->typ])) {
            $this->children[$child->typ] = [];
        }

        $this->children[$child->typ][] = $child;
        usort($this->children[$child->typ], function ($a, $b) {
            return $a->label <=> $b->label;
        });

        $this->tailSort($child->typ);

        return $hn;
    }

    /**
     * Push nodes with tail '/' to the end of the list.
     */
    private function tailSort(int $nodeType): void
    {
        if (!isset($this->children[$nodeType])) {
            return;
        }

        $nodes = &$this->children[$nodeType];
        for ($i = count($nodes) - 1; $i >= 0; --$i) {
            if ($nodes[$i]->typ > NodeType::STATIC && $nodes[$i]->tail === ord('/')) {
                // swap to the end of the list
                $temp = $nodes[$i];
                $nodes[$i] = $nodes[count($nodes) - 1];
                $nodes[count($nodes) - 1] = $temp;

                return;
            }
        }
    }

    /**
     * Replace the child nodes with the specified tag and tail.
     */
    public function replaceChild(string $labelChar, int $tail, self $child): void
    {
        $label = ord($labelChar);
        $nodeType = $child->typ;

        if (!isset($this->children[$nodeType])) {
            throw new \RuntimeException('Chi: missing child node to replace');
        }

        for ($i = 0; $i < count($this->children[$nodeType]); ++$i) {
            if ($this->children[$nodeType][$i]->label === $label
                && $this->children[$nodeType][$i]->tail === $tail) {
                $this->children[$nodeType][$i] = $child;
                $this->children[$nodeType][$i]->label = $label;
                $this->children[$nodeType][$i]->tail = $tail;

                return;
            }
        }

        throw new \RuntimeException('Chi: missing child node to replace');
    }

    /**
     * Get the child node with the specified type, label, tail, and prefix.
     */
    public function getEdge(int $ntyp, int $label, int $tail, string $prefix): ?self
    {
        if (!isset($this->children[$ntyp])) {
            return null;
        }

        $nds = $this->children[$ntyp];
        foreach ($nds as $node) {
            if ($node->label === $label && $node->tail === $tail) {
                if (NodeType::REGEXP === $ntyp && $node->prefix !== $prefix) {
                    continue;
                }

                return $node;
            }
        }

        return null;
    }

    /**
     * Set the handler for the specified method type on the node.
     */
    public function setEndpoint(int $method, callable $handler, string $pattern): void
    {
        $paramKeys = self::patParamKeys($pattern);

        if (($method & MethodType::STUB) === MethodType::STUB) {
            $this->getEndpoint(MethodType::STUB)->handler = $handler;
        }

        if (($method & MethodType::$ALL) === MethodType::$ALL) {
            $endpoint = $this->getEndpoint(MethodType::$ALL);
            $endpoint->handler = $handler;
            $endpoint->pattern = $pattern;
            $endpoint->paramKeys = $paramKeys;

            foreach (MethodType::$methodMap as $m) {
                $endpoint = $this->getEndpoint($m);
                $endpoint->handler = $handler;
                $endpoint->pattern = $pattern;
                $endpoint->paramKeys = $paramKeys;
            }
        } else {
            $endpoint = $this->getEndpoint($method);
            $endpoint->handler = $handler;
            $endpoint->pattern = $pattern;
            $endpoint->paramKeys = $paramKeys;
        }
    }

    /**
     * Finds the route node for the given method and path.
     */
    public function findRoute(Context $rctx, int $method, string $path): ?array
    {
        $rctx->routePattern = '';
        $rctx->routeParams->keys = [];
        $rctx->routeParams->values = [];

        $rn = $this->findRouteNode($rctx, $method, $path);
        if (null === $rn) {
            return null;
        }

        if (isset($rn->endpoints[$method]) && '' !== $rn->endpoints[$method]->pattern) {
            $rctx->routePattern = $rn->endpoints[$method]->pattern;
            $rctx->routePatterns[] = $rctx->routePattern;
        }

        return [
            'node' => $rn,
            'endpoints' => $rn->endpoints,
            'handler' => isset($rn->endpoints[$method]) ? $rn->endpoints[$method]->handler : null,
        ];
    }

    /**
     * Recursively finds the route node for the given method and path.
     * It's like searching through a multi-dimensional radix trie.
     */
    private function findRouteNode(Context $rctx, int $method, string $path): ?self
    {
        $nn = $this;
        $search = $path;

        foreach ($nn->children as $t => $nds) {
            $ntyp = $t;
            if (empty($nds)) {
                continue;
            }

            $xn = null;
            $xsearch = $search;

            $label = strlen($search) > 0 ? ord($search[0]) : 0;

            switch ($ntyp) {
                case NodeType::STATIC:
                    $xn = $this->findEdgeNode($nds, $label);
                    if (null === $xn || !str_starts_with($xsearch, $xn->prefix)) {
                        continue 2;
                    }
                    $xsearch = substr($xsearch, strlen($xn->prefix));
                    break;

                case NodeType::PARAM:
                case NodeType::REGEXP:
                    // short-circuit and return no matching route for empty param values
                    if ('' === $xsearch) {
                        continue 2;
                    }

                    // serially loop through each node grouped by the tail delimiter
                    foreach ($nds as $node) {
                        $xn = $node;

                        // 查找参数分隔符
                        $p = strpos($xsearch, chr($xn->tail));

                        if (false === $p) {
                            if ($xn->tail === ord('/')) {
                                $p = strlen($xsearch);
                            } else {
                                continue;
                            }
                        } elseif (NodeType::REGEXP === $ntyp && 0 === $p) {
                            continue;
                        }

                        if (NodeType::REGEXP === $ntyp && $xn->rex) {
                            if (!preg_match('/'.$xn->prefix.'/', substr($xsearch, 0, $p))) {
                                continue;
                            }
                        } elseif (str_contains(substr($xsearch, 0, $p), '/')) {
                            // avoid a match across path segments
                            continue;
                        }

                        $prevlen = count($rctx->routeParams->values);
                        $rctx->routeParams->values[] = substr($xsearch, 0, $p);
                        $xsearch = substr($xsearch, $p);

                        if ('' === $xsearch) {
                            if ($this->isLeaf($xn)) {
                                if (isset($xn->endpoints[$method]) && null !== $xn->endpoints[$method]->handler) {
                                    $rctx->routeParams->keys = array_merge(
                                        $rctx->routeParams->keys,
                                        $xn->endpoints[$method]->paramKeys
                                    );

                                    return $xn;
                                }

                                foreach ($xn->endpoints as $endpointMethod => $endpoint) {
                                    if ($endpointMethod === MethodType::$ALL || MethodType::STUB === $endpointMethod) {
                                        continue;
                                    }
                                    $rctx->methodsAllowed[] = $endpointMethod;
                                }

                                // flag that the routing context found a route, but not a corresponding
                                // supported method
                                $rctx->methodNotAllowed = true;
                            }
                        }

                        // recursively find the next node on this branch
                        $fin = $xn->findRouteNode($rctx, $method, $xsearch);
                        if (null !== $fin) {
                            return $fin;
                        }

                        // not found on this branch, reset vars
                        $rctx->routeParams->values = array_slice($rctx->routeParams->values, 0, $prevlen);
                        $xsearch = $search;
                    }

                    $rctx->routeParams->values[] = '';
                    break;

                default:
                    // catch-all nodes
                    $rctx->routeParams->values[] = $search;
                    $xn = $nds[0];
                    $xsearch = '';
                    break;
            }

            if (null === $xn) {
                continue;
            }

            // did we find it yet?
            if ('' === $xsearch) {
                if ($this->isLeaf($xn)) {
                    if (isset($xn->endpoints[$method]) && null !== $xn->endpoints[$method]->handler) {
                        $rctx->routeParams->keys = array_merge(
                            $rctx->routeParams->keys,
                            $xn->endpoints[$method]->paramKeys
                        );

                        return $xn;
                    }

                    foreach ($xn->endpoints as $endpointMethod => $endpoint) {
                        if ($endpointMethod === MethodType::$ALL || MethodType::STUB === $endpointMethod) {
                            continue;
                        }
                        $rctx->methodsAllowed[] = $endpointMethod;
                    }

                    // flag that the routing context found a route, but not a corresponding
                    // supported method
                    $rctx->methodNotAllowed = true;
                }
            }

            // recursively find the next node..
            $fin = $xn->findRouteNode($rctx, $method, $xsearch);
            if (null !== $fin) {
                return $fin;
            }

            // Did not find final handler, let's remove the param here if it was set
            if ($xn->typ > NodeType::STATIC) {
                if (count($rctx->routeParams->values) > 0) {
                    array_pop($rctx->routeParams->values);
                }
            }
        }

        return null;
    }

    /**
     * Finds the node with the specified label in the array of nodes.
     */
    private function findEdgeNode(array $nodes, int $label): ?self
    {
        $low = 0;
        $high = count($nodes) - 1;

        while ($low <= $high) {
            $mid = $low + intdiv($high - $low, 2);

            if ($label > $nodes[$mid]->label) {
                $low = $mid + 1;
            } elseif ($label < $nodes[$mid]->label) {
                $high = $mid - 1;
            } else {
                return $nodes[$mid];
            }
        }

        return null;
    }

    /**
     * Finds the node for the given node type and label.
     */
    public function findEdge(int $ntyp, int $label): ?self
    {
        if (!isset($this->children[$ntyp])) {
            return null;
        }

        $nds = $this->children[$ntyp];

        return match ($ntyp) {
            NodeType::STATIC, NodeType::PARAM, NodeType::REGEXP => $this->findEdgeNode($nds, $label),
            default => $nds[0] ?? null,
        };
    }

    /**
     * Check if the node is a leaf node.
     */
    private function isLeaf(self $node): bool
    {
        return !empty($node->endpoints);
    }

    /**
     * Returns the next segment details from a pattern:
     * node type, param key, regexp string, param tail byte, param starting index, param ending index
     */
    public static function patNextSegment(string $pattern): array
    {
        $ps = strpos($pattern, '{');
        $ws = strpos($pattern, '*');

        if (false === $ps && false === $ws) {
            return [NodeType::STATIC, '', '', 0, 0, strlen($pattern)]; // we return the entire thing
        }

        // Sanity check
        if (false !== $ps && false !== $ws && $ws < $ps) {
            throw new \RuntimeException("Chi: wildcard '*' must be the last pattern in a route, otherwise use a '{param}'");
        }

        $tail = ord('/'); // Default endpoint tail to / byte

        if (false !== $ps) {
            // Param/Regexp pattern is next
            $nt = NodeType::PARAM;

            // Read to closing } taking into account opens and closes in curl count (cc)
            $cc = 0;
            $pe = $ps;
            for ($i = 0; $i < strlen($pattern) - $ps; ++$i) {
                $c = $pattern[$ps + $i];
                if ('{' === $c) {
                    ++$cc;
                } elseif ('}' === $c) {
                    --$cc;
                    if (0 === $cc) {
                        $pe = $ps + $i;
                        break;
                    }
                }
            }

            if ($pe === $ps) {
                throw new \RuntimeException("Chi: route param closing delimiter '}' is missing");
            }

            $key = substr($pattern, $ps + 1, $pe - $ps - 1);
            ++$pe; // set end to next position

            if ($pe < strlen($pattern)) {
                $tail = ord($pattern[$pe]);
            }

            // check if the param contains a regex
            if (str_contains($key, ':')) {
                [$key, $rexpat] = explode(':', $key, 2);
                $nt = NodeType::REGEXP;

                // make sure the regex is valid
                if (!empty($rexpat)) {
                    if ('^' !== $rexpat[0]) {
                        $rexpat = '^'.$rexpat;
                    }
                    if (!str_ends_with($rexpat, '$')) {
                        $rexpat .= '$';
                    }
                }
            } else {
                $rexpat = '';
            }

            return [$nt, $key, $rexpat, $tail, $ps, $pe];
        }

        // Wildcard pattern as finale
        if ($ws < strlen($pattern) - 1) {
            throw new \RuntimeException("Chi: wildcard '*' must be the last value in a route. trim trailing text or use a '{param}' instead");
        }

        return [NodeType::CATCHALL, '*', '', 0, $ws, strlen($pattern)];
    }

    /**
     * Returns the parameter keys from a pattern.
     */
    public static function patParamKeys(string $pattern): array
    {
        $pat = $pattern;
        $paramKeys = [];

        while (true) {
            [$ptyp, $paramKey, $rexpat, $tail, $start, $end] = self::patNextSegment($pat);

            if (NodeType::STATIC === $ptyp) {
                return $paramKeys;
            }

            foreach ($paramKeys as $existingKey) {
                if ($existingKey === $paramKey) {
                    throw new \RuntimeException("Chi: routing pattern '{$pattern}' contains duplicate param key '{$paramKey}'");
                }
            }

            $paramKeys[] = $paramKey;
            $pat = substr($pat, $end);
        }
    }

    /**
     * longestPrefix finds the length of the shared prefix of two strings.
     */
    public static function longestPrefix(string $k1, string $k2): int
    {
        $max = min(strlen($k1), strlen($k2));
        $i = 0;

        while ($i < $max && $k1[$i] === $k2[$i]) {
            ++$i;
        }

        return $i;
    }

    /**
     * Find a pattern in the routing tree.
     */
    public function findPattern(string $pattern): bool
    {
        $nn = $this;

        foreach ($nn->children as $nds) {
            if (empty($nds)) {
                continue;
            }

            $n = $this->findEdge($nds[0]->typ, ord($pattern[0]));
            if (null === $n) {
                continue;
            }

            switch ($n->typ) {
                case NodeType::STATIC:
                    $idx = self::longestPrefix($pattern, $n->prefix);
                    if ($idx < strlen($n->prefix)) {
                        continue 2;
                    }
                    break;

                case NodeType::PARAM:
                case NodeType::REGEXP:
                    $idx = strpos($pattern, '}') + 1;
                    break;

                case NodeType::CATCHALL:
                    $idx = self::longestPrefix($pattern, '*');
                    break;

                default:
                    throw new \RuntimeException('Chi: unknown node type');
            }

            $xpattern = substr($pattern, $idx);
            if ('' === $xpattern) {
                return true;
            }

            return $n->findPattern($xpattern);
        }

        return false;
    }

    /**
     * Returns all the routes in the routing tree.
     */
    public function routes(): array
    {
        $rts = [];

        $this->walk(function ($eps) use (&$rts) {
            if (isset($eps[MethodType::STUB]) && null !== $eps[MethodType::STUB]->handler) {
                return false;
            }

            // Group methodHandlers by unique patterns
            $pats = [];

            foreach ($eps as $mt => $h) {
                if ('' === $h->pattern) {
                    continue;
                }

                if (!isset($pats[$h->pattern])) {
                    $pats[$h->pattern] = [];
                }

                $pats[$h->pattern][$mt] = $h;
            }

            foreach ($pats as $p => $mh) {
                $hs = [];
                if (isset($mh[MethodType::$ALL]) && null !== $mh[MethodType::$ALL]->handler) {
                    $hs['*'] = $mh[MethodType::$ALL]->handler;
                }

                foreach ($mh as $mt => $h) {
                    if (null === $h->handler) {
                        continue;
                    }

                    $m = self::methodTypString($mt);
                    if ('' === $m) {
                        continue;
                    }

                    $hs[$m] = $h->handler;
                }

                $rt = new Route($hs, $p);
                $rts[] = $rt;
            }

            return false;
        });

        return $rts;
    }

    /**
     * Walk the routing tree and apply the callback function to each endpoint.
     */
    public function walk(callable $fn): bool
    {
        // Visit the leaf values if any
        if ((!empty($this->endpoints))
            && $fn($this->endpoints)) {
            return true;
        }

        // Recurse on the children
        foreach ($this->children as $nodes) {
            foreach ($nodes as $cn) {
                if ($cn->walk($fn)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Convert method type to string.
     */
    public static function methodTypString(int $method): string
    {
        return MethodType::$reverseMethodMap[$method] ?? '';
    }
}
