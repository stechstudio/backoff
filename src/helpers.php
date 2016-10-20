<?php
if(!function_exists('backoff')) {
    function backoff($callback, $maxAttempts = null, $strategy = null, $waitCap = null)
    {
        return (new \STS\Backoff\Backoff($maxAttempts, $strategy, $waitCap))->run($callback);
    }
}
