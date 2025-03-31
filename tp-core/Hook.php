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

namespace TP;

class Hook implements \Iterator, \ArrayAccess
{
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
    private array $current_priority = [];

    /**
     * Number of levels this hook can be recursively called.
     *
     * @since 1.0.0
     */
    private int $nesting_level = 0;

    /**
     * Flag for if we're current doing an action, rather than a filter.
     *
     * @since 1.0.0
     */
    private bool $doing_action = false;

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @param string   $tag             the name of the filter to hook the $function_to_add callback to
     * @param callable $function_to_add the callback to be run when the filter is applied
     * @param int      $priority        The order in which the functions associated with a particular action
     *                                  are executed. Lower numbers correspond with earlier execution,
     *                                  and functions with the same priority are executed in the order
     *                                  in which they were added to the action.
     * @param int      $accepted_args   the number of arguments the function accepts
     *
     * @since 1.0.0
     */
    public function add_filter(string $tag, callable $function_to_add, int $priority, int $accepted_args): void
    {
        $idx = $this->build_unique_filter_id($tag, $function_to_add);

        $priority_existed = isset($this->callbacks[$priority]);

        $this->callbacks[$priority][$idx] = [
            'function' => $function_to_add,
            'accepted_args' => $accepted_args,
        ];

        // If we're adding a new priority to the list, put them back in sorted order.
        if (!$priority_existed && count($this->callbacks) > 1) {
            ksort($this->callbacks, SORT_NUMERIC);
        }

        if ($this->nesting_level > 0) {
            $this->resort_active_iterations($priority, $priority_existed);
        }
    }

    /**
     * Handles resetting callback priority keys mid-iteration.
     *
     * @param false|int $new_priority     Optional. The priority of the new filter being added. Default false,
     *                                    for no priority being added.
     * @param bool      $priority_existed Optional. Flag for whether the priority already existed before the new
     *                                    filter was added. Default false.
     *
     * @since 1.0.0
     */
    private function resort_active_iterations(false|int $new_priority = false, bool $priority_existed = false): void
    {
        $new_priorities = array_keys($this->callbacks);

        // If there are no remaining hooks, clear out all running iterations.
        if (!$new_priorities) {
            foreach ($this->iterations as $index => $iteration) {
                $this->iterations[$index] = $new_priorities;
            }

            return;
        }

        $min = min($new_priorities);
        foreach ($this->iterations as $index => &$iteration) {
            $current = current($iteration);
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if (false === $current) {
                continue;
            }

            $iteration = $new_priorities;

            if ($current < $min) {
                array_unshift($iteration, $current);
                continue;
            }

            while (current($iteration) < $current) {
                if (false === next($iteration)) {
                    break;
                }
            }

            // If we have a new priority that didn't exist, but ::apply_filters() or ::do_action() thinks it's the current priority...
            if ($new_priority === $this->current_priority[$index] && !$priority_existed) {
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
                } elseif ($new_priority !== $prev) {
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
     * @param string   $tag                the filter hook to which the function to be removed is hooked
     * @param callable $function_to_remove the callback to be removed from running when the filter is applied
     * @param int      $priority           the exact priority used when adding the original filter callback
     *
     * @return bool whether the callback existed before it was removed
     *
     * @since 1.0.0
     */
    public function remove_filter(string $tag, callable $function_to_remove, int $priority): bool
    {
        $function_key = $this->build_unique_filter_id($tag, $function_to_remove);

        $exists = isset($this->callbacks[$priority][$function_key]);
        if ($exists) {
            unset($this->callbacks[$priority][$function_key]);
            if (!$this->callbacks[$priority]) {
                unset($this->callbacks[$priority]);
                if ($this->nesting_level > 0) {
                    $this->resort_active_iterations();
                }
            }
        }

        return $exists;
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * When using the `$function_to_check` argument, this function may return a non-boolean value
     * that evaluates to false (e.g. 0), so use the `===` operator for testing the return value.
     *
     * @param string         $tag               Optional. The name of the filter hook. Default empty.
     * @param callable|false $function_to_check Optional. The callback to check for. Default false.
     *
     * @return int|bool If `$function_to_check` is omitted, returns boolean for whether the hook has
     *                  anything registered. When checking a specific function, the priority of that
     *                  hook is returned, or false if the function is not attached.
     *
     * @since 1.0.0
     */
    public function has_filter(string $tag = '', callable|false $function_to_check = false): bool|int
    {
        if (false === $function_to_check) {
            return $this->has_filters();
        }

        $function_key = $this->build_unique_filter_id($tag, $function_to_check);
        if (!$function_key) {
            return false;
        }

        foreach ($this->callbacks as $priority => $callbacks) {
            if (isset($callbacks[$function_key])) {
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
    public function has_filters(): bool
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
    public function remove_all_filters(false|int $priority = false): void
    {
        if (!$this->callbacks) {
            return;
        }

        if (false === $priority) {
            $this->callbacks = [];
        } elseif (isset($this->callbacks[$priority])) {
            unset($this->callbacks[$priority]);
        }

        if ($this->nesting_level > 0) {
            $this->resort_active_iterations();
        }
    }

    /**
     * Calls the callback functions that have been added to a filter hook.
     *
     * @param mixed $value the value to filter
     * @param array $args  Additional parameters to pass to the callback functions.
     *                     This array is expected to include $value at index 0.
     *
     * @return mixed the filtered value after all hooked functions are applied to it
     *
     * @since 1.0.0
     */
    public function apply_filters(mixed $value, array $args): mixed
    {
        if (!$this->callbacks) {
            return $value;
        }

        $nesting_level = $this->nesting_level++;

        $this->iterations[$nesting_level] = array_keys($this->callbacks);
        $num_args = count($args);

        do {
            $this->current_priority[$nesting_level] = current($this->iterations[$nesting_level]);
            $priority = $this->current_priority[$nesting_level];

            foreach ($this->callbacks[$priority] as $the_) {
                if (!$this->doing_action) {
                    $args[0] = $value;
                }

                // Avoid the array_slice() if possible.
                if (0 == $the_['accepted_args']) {
                    $value = call_user_func($the_['function']);
                } elseif ($the_['accepted_args'] >= $num_args) {
                    $value = call_user_func_array($the_['function'], $args);
                } else {
                    $value = call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));
                }
            }
        } while (false !== next($this->iterations[$nesting_level]));

        unset($this->iterations[$nesting_level]);
        unset($this->current_priority[$nesting_level]);

        --$this->nesting_level;

        return $value;
    }

    /**
     * Calls the callback functions that have been added to an action hook.
     *
     * @param array $args parameters to pass to the callback functions
     *
     * @since 1.0.0
     */
    public function do_action(array $args): void
    {
        $this->doing_action = true;
        $this->apply_filters('', $args);

        // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
        if (!$this->nesting_level) {
            $this->doing_action = false;
        }
    }

    /**
     * Processes the functions hooked into the 'all' hook.
     *
     * @param array $args Arguments to pass to the hook callbacks. Passed by reference.
     *
     * @since 1.0.0
     */
    public function do_all_hook(array $args): void
    {
        $nesting_level = $this->nesting_level++;
        $this->iterations[$nesting_level] = array_keys($this->callbacks);

        do {
            $priority = current($this->iterations[$nesting_level]);
            foreach ($this->callbacks[$priority] as $the_) {
                call_user_func_array($the_['function'], $args);
            }
        } while (false !== next($this->iterations[$nesting_level]));

        unset($this->iterations[$nesting_level]);
        --$this->nesting_level;
    }

    /**
     * Return the current priority level of the currently running iteration of the hook.
     *
     * @return int|false If the hook is running, return the current priority level. If it isn't running, return false.
     *
     * @since 1.0.0
     */
    public function current_priority(): false|int
    {
        if (false === current($this->iterations)) {
            return false;
        }

        return current(current($this->iterations));
    }

    /**
     * Normalizes filters set up before WordPress has initialized to WP_Hook objects.
     *
     * The `$filters` parameter should be an array keyed by hook name, with values
     * containing either:
     *
     *  - A `WP_Hook` instance
     *  - An array of callbacks keyed by their priorities
     *
     * Examples:
     *
     *     $filters = array(
     *         'wp_fatal_error_handler_enabled' => array(
     *             10 => array(
     *                 array(
     *                     'accepted_args' => 0,
     *                     'function'      => function() {
     *                         return false;
     *                     },
     *                 ),
     *             ),
     *         ),
     *     );
     *
     * @param array $filters Filters to normalize. See documentation above for details.
     *
     * @return Hook[] array of normalized filters
     *
     * @since 1.0.0
     */
    public static function build_preinitialized_hooks($filters): array
    {
        /** @var Hook[] $normalized */
        $normalized = [];

        foreach ($filters as $tag => $callback_groups) {
            if ($callback_groups instanceof self) {
                $normalized[$tag] = $callback_groups;
                continue;
            }
            $hook = new self();

            // Loop through callback groups.
            foreach ($callback_groups as $priority => $callbacks) {
                // Loop through callbacks.
                foreach ($callbacks as $cb) {
                    $hook->add_filter($tag, $cb['function'], $priority, $cb['accepted_args']);
                }
            }
            $normalized[$tag] = $hook;
        }

        return $normalized;
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
     * @param string                $hook_name Unused. The name of the filter to build ID for.
     * @param callable|array|string $callback  The callback to generate ID for. The callback may
     *                                         or may not exist.
     *
     * @return string unique function ID for usage as array key
     *
     * @since 1.0.0
     */
    private function build_unique_filter_id(string $hook_name, callable|array|string $callback): string
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
