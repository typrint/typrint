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

namespace TP\Hook;

use TP\Utils\Once;

class Hook implements \Iterator, \ArrayAccess
{
    private static Hook $instance;

    private static Once $once;

    /**
     * Hook callbacks.
     *
     * @since 1.0.0
     */
    public array $callbacks = [];

    /**
     * The priority keys of actively running iterations of a hook.
     *
     * @since 1.0.0
     */
    private array $iterations = [];

    /**
     * The current priority of actively running iterations of a hook.
     *
     * @since 1.0.0
     */
    private array $currentPriority = [];

    /**
     * Number of levels this hook can be recursively called.
     *
     * @since 1.0.0
     */
    private int $nestingLevel = 0;

    /**
     * Flag for if we're current doing an action, rather than a filter.
     *
     * @since 1.0.0
     */
    private bool $doingAction = false;

    public static function init(): void
    {
        self::$once = new Once();
    }

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once->do(fn () => self::$instance = new self());
        }

        return self::$instance;
    }

    /**
     * Hooks a function or method to a specific action.
     *
     * @param string   $tag           the name of the action to hook the $functionToAdd callback to
     * @param callable $functionToAdd the callback to be run when the action is called
     * @param int      $priority      The order in which the functions associated with a particular action
     *                                are executed. Lower numbers correspond with earlier execution,
     *                                and functions with the same priority are executed in the order
     *                                in which they were added to the action.
     * @param int      $acceptedArgs  the number of arguments the function accepts
     *
     * @since 1.0.0
     */
    public function addAction(string $tag, callable $functionToAdd, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->addFilter($tag, $functionToAdd, $priority, $acceptedArgs);
    }

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @param string   $tag           the name of the filter to hook the $functionToAdd callback to
     * @param callable $functionToAdd the callback to be run when the filter is applied
     * @param int      $priority      The order in which the functions associated with a particular action
     *                                are executed. Lower numbers correspond with earlier execution,
     *                                and functions with the same priority are executed in the order
     *                                in which they were added to the action.
     * @param int      $acceptedArgs  the number of arguments the function accepts
     *
     * @since 1.0.0
     */
    public function addFilter(string $tag, callable $functionToAdd, int $priority = 10, int $acceptedArgs = 1): void
    {
        $idx = $this->buildUniqueFilterId($tag, $functionToAdd);

        $priorityExisted = isset($this->callbacks[$tag][$priority]);

        $this->callbacks[$tag][$priority][$idx] = [
            'function' => $functionToAdd,
            'acceptedArgs' => $acceptedArgs,
        ];

        // If we're adding a new priority to the list, put them back in sorted order.
        if (!$priorityExisted && isset($this->callbacks[$tag]) && count($this->callbacks[$tag]) > 1) {
            ksort($this->callbacks[$tag], SORT_NUMERIC);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations($tag, $priority, $priorityExisted);
        }
    }

    /**
     * Handles resetting callback priority keys mid-iteration.
     *
     * @param string    $tag             The hook name
     * @param false|int $newPriority     Optional. The priority of the new filter being added. Default false,
     *                                   for no priority being added.
     * @param bool      $priorityExisted Optional. Flag for whether the priority already existed before the new
     *                                   filter was added. Default false.
     *
     * @since 1.0.0
     */
    private function resortActiveIterations(string $tag, false|int $newPriority = false, bool $priorityExisted = false): void
    {
        if (!isset($this->callbacks[$tag])) {
            return;
        }

        $newPriorities = array_keys($this->callbacks[$tag]);

        // If there are no remaining hooks, clear out all running iterations.
        if (!$newPriorities) {
            foreach ($this->iterations as $index => $iteration) {
                $this->iterations[$index] = $newPriorities;
            }

            return;
        }

        $min = min($newPriorities);
        foreach ($this->iterations as $index => &$iteration) {
            $current = current($iteration);
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if (false === $current) {
                continue;
            }

            $iteration = $newPriorities;

            if ($current < $min) {
                array_unshift($iteration, $current);
                continue;
            }

            while (current($iteration) < $current) {
                if (false === next($iteration)) {
                    break;
                }
            }

            // If we have a new priority that didn't exist, but ::applyFilters() or ::doAction() thinks it's the current priority...
            if ($newPriority === $this->currentPriority[$index] && !$priorityExisted) {
                /*
                 * ...and the new priority is the same as what $this->iterations thinks is the previous
                 * priority, we need to move back to it.
                 */

                if (false === current($iteration)) {
                    // If we've already moved off the end of the array, go back to the last element.
                    $prev = end($iteration);
                } else {
                    // Otherwise, just go back to the previous element.
                    $prev = prev($iteration);
                }
                if (false === $prev) {
                    // Start of the array. Reset, and go about our day.
                    reset($iteration);
                } elseif ($newPriority !== $prev) {
                    // Previous wasn't the same. Move forward again.
                    next($iteration);
                }
            }
        }
        unset($iteration);
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @param string   $tag              the filter hook to which the function to be removed is hooked
     * @param callable $functionToRemove the callback to be removed from running when the filter is applied
     * @param int      $priority         the exact priority used when adding the original filter callback
     *
     * @return bool whether the callback existed before it was removed
     *
     * @since 1.0.0
     */
    public function removeFilter(string $tag, callable $functionToRemove, int $priority = 10): bool
    {
        $functionKey = $this->buildUniqueFilterId($tag, $functionToRemove);

        $exists = isset($this->callbacks[$tag][$priority][$functionKey]);
        if ($exists) {
            unset($this->callbacks[$tag][$priority][$functionKey]);
            if (empty($this->callbacks[$tag][$priority])) {
                unset($this->callbacks[$tag][$priority]);
                if (empty($this->callbacks[$tag])) {
                    unset($this->callbacks[$tag]);
                }
                if ($this->nestingLevel > 0) {
                    $this->resortActiveIterations($tag);
                }
            }
        }

        return $exists;
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * When using the `$functionToCheck` argument, this function may return a non-boolean value
     * that evaluates to false (e.g. 0), so use the `===` operator for testing the return value.
     *
     * @param string         $tag             Optional. The name of the filter hook. Default empty.
     * @param callable|false $functionToCheck Optional. The callback to check for. Default false.
     *
     * @return int|bool If `$functionToCheck` is omitted, returns boolean for whether the hook has
     *                  anything registered. When checking a specific function, the priority of that
     *                  hook is returned, or false if the function is not attached.
     *
     * @since 1.0.0
     */
    public function hasFilter(string $tag = '', callable|false $functionToCheck = false): bool|int
    {
        if (false === $functionToCheck) {
            return !empty($this->callbacks[$tag]);
        }

        $functionKey = $this->buildUniqueFilterId($tag, $functionToCheck);
        if (!$functionKey) {
            return false;
        }

        foreach ($this->callbacks[$tag] as $priority => $callbacks) {
            if (isset($callbacks[$functionKey])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Checks if any callbacks have been registered for this hook.
     *
     * @return bool true if callbacks have been registered for the current hook, otherwise false
     *
     * @since 1.0.0
     */
    public function hasFilters(): bool
    {
        foreach ($this->callbacks as $callbacks) {
            if ($callbacks) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes all callbacks from the current filter.
     *
     * @param false|int $priority Optional. The priority number to remove. Default false.
     *
     * @since 1.0.0
     */
    public function removeAllFilters(false|int $priority = false): void
    {
        if (!$this->callbacks) {
            return;
        }

        if (false === $priority) {
            $this->callbacks = [];
        } elseif (isset($this->callbacks[$priority])) {
            unset($this->callbacks[$priority]);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations();
        }
    }

    /**
     * Calls the callback functions that have been added to a filter hook.
     *
     * @param string $tag   The name of the filter hook
     * @param mixed  $value The value to filter
     * @param array  $args  Additional parameters to pass to the callback functions.
     *                      This array is expected to include $value at index 0.
     *
     * @return mixed the filtered value after all hooked functions are applied to it
     *
     * @since 1.0.0
     */
    public function applyFilter(string $tag, mixed $value, array $args = []): mixed
    {
        array_unshift($args, $value);

        if (!isset($this->callbacks[$tag]) || empty($this->callbacks[$tag])) {
            return $value;
        }

        $nestingLevel = $this->nestingLevel++;

        $this->iterations[$nestingLevel] = array_keys($this->callbacks[$tag]);
        $numArgs = count($args);

        do {
            $this->currentPriority[$nestingLevel] = current($this->iterations[$nestingLevel]);
            $priority = $this->currentPriority[$nestingLevel];

            foreach ($this->callbacks[$tag][$priority] as $the_) {
                if (!$this->doingAction) {
                    $args[0] = $value;
                }

                // Avoid the array_slice() if possible.
                if (0 == $the_['acceptedArgs']) {
                    $value = call_user_func($the_['function']);
                } elseif ($the_['acceptedArgs'] >= $numArgs) {
                    $value = call_user_func_array($the_['function'], $args);
                } else {
                    $value = call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['acceptedArgs']));
                }
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        unset($this->currentPriority[$nestingLevel]);

        --$this->nestingLevel;

        return $value;
    }

    /**
     * Calls the callback functions that have been added to an action hook.
     *
     * @param string $tag  The name of the action hook
     * @param array  $args Parameters to pass to the callback functions
     *
     * @since 1.0.0
     */
    public function doAction(string $tag, array $args = []): void
    {
        $this->doingAction = true;
        $this->applyFilter($tag, '', $args);

        // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
        if (!$this->nestingLevel) {
            $this->doingAction = false;
        }
    }

    /**
     * Return the current priority level of the currently running iteration of the hook.
     *
     * @return int|false If the hook is running, return the current priority level. If it isn't running, return false.
     *
     * @since 1.0.0
     */
    public function currentPriority(): false|int
    {
        if (false === current($this->iterations)) {
            return false;
        }

        return current(current($this->iterations));
    }

    /**
     * Determines whether an offset value exists.
     *
     * @param mixed $offset an offset to check for
     *
     * @return bool true if the offset exists, false otherwise
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset): bool
    {
        return isset($this->callbacks[$offset]);
    }

    /**
     * Retrieves a value at a specified offset.
     *
     * @param mixed $offset the offset to retrieve
     *
     * @return mixed if set, the value at the specified offset, null otherwise
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->callbacks[$offset] ?? null;
    }

    /**
     * Sets a value at a specified offset.
     *
     * @param mixed $offset the offset to assign the value to
     * @param mixed $value  the value to set
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Unsets a specified offset.
     *
     * @param mixed $offset the offset to unset
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->callbacks[$offset]);
    }

    /**
     * Returns the current element.
     *
     * @return array of callbacks at current priority
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/iterator.current.php
     */
    public function current(): array
    {
        return current($this->callbacks);
    }

    /**
     * Moves forward to the next element.
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        next($this->callbacks);
    }

    /**
     * Returns the key of the current element.
     *
     * @return string|int|null Returns current priority on success, or NULL on failure
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/iterator.key.php
     */
    public function key(): string|int|null
    {
        return key($this->callbacks);
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool whether the current position is valid
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool
    {
        return null !== key($this->callbacks);
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     * @since 1.0.0
     * @see https://www.php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        reset($this->callbacks);
    }

    /**
     * Builds a unique string ID for a hook callback function.
     *
     * Functions and static method callbacks are just returned as strings and
     * shouldn't have any speed penalty.
     *
     * @param string                $hookName Unused. The name of the filter to build ID for.
     * @param callable|array|string $callback The callback to generate ID for. The callback may
     *                                        or may not exist.
     *
     * @return string unique function ID for usage as array key
     *
     * @since 1.0.0
     */
    private function buildUniqueFilterId(string $hookName, callable|array|string $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_object($callback)) {
            // Closures are currently implemented as objects.
            $callback = [$callback, ''];
        } else {
            $callback = (array) $callback;
        }

        if (is_object($callback[0])) {
            // Object class calling.
            return spl_object_hash($callback[0]).$callback[1];
        } elseif (is_string($callback[0])) {
            // Static calling.
            return $callback[0].'::'.$callback[1];
        }

        return '';
    }
}
