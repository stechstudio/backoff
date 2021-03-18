# JBZoo / Retry

[![Build Status](https://travis-ci.org/JBZoo/Retry.svg)](https://travis-ci.org/JBZoo/Retry)    [![Coverage Status](https://coveralls.io/repos/JBZoo/Retry/badge.svg)](https://coveralls.io/github/JBZoo/Retry)    [![Psalm Coverage](https://shepherd.dev/github/JBZoo/Retry/coverage.svg)](https://shepherd.dev/github/JBZoo/Retry)    
[![Stable Version](https://poser.pugx.org/jbzoo/retry/version)](https://packagist.org/packages/jbzoo/retry)    [![Latest Unstable Version](https://poser.pugx.org/jbzoo/retry/v/unstable)](https://packagist.org/packages/jbzoo/retry)    [![Dependents](https://poser.pugx.org/jbzoo/retry/dependents)](https://packagist.org/packages/jbzoo/retry/dependents?order_by=downloads)    [![GitHub Issues](https://img.shields.io/github/issues/jbzoo/retry)](https://github.com/JBZoo/Retry/issues)    [![Total Downloads](https://poser.pugx.org/jbzoo/retry/downloads)](https://packagist.org/packages/jbzoo/retry/stats)    [![GitHub License](https://img.shields.io/github/license/jbzoo/retry)](https://github.com/JBZoo/Retry/blob/master/LICENSE)


 1. 4 retry strategies (plus the ability to use your own)
 2. Optional jitter / randomness to spread out retries and minimize collisions
 3. Wait time cap
 4. Callbacks for custom retry logic or error handling


Notes:
 * This is a fork. You can find the original project [here](https://github.com/stechstudio/retry).
 * Now the codebase super strict, and it's covered with tests as much as possible. The original author is great, but the code was smelly :) It's sooo easy, and it took just one my evening... ;) 
 * I don't like wording "backoff" in the code. Yeah, it's fun but... I believe "retry" is more obvious. Sorry :)
 * There is nothing wrong to use import instead of global namespace for function.
 * At least my project has [aliases](./src/aliases.php) for backward compatibility with the original. ;)


## Installation

```
composer require jbzoo/retry
```

## Defaults

This library provides sane defaults, so you can hopefully just jump in for most of your use cases.

By default, the retry is quadratic with a 100ms base time (`attempt^2 * 100`), a max of 5 retries, and no jitter.

## Quickstart

The simplest way to use Retry is with the `retry` helper function:

```php
use function JBZoo\Retry\retry;

$result = retry(function() {
    return doSomeWorkThatMightFail();
});
```

If successful `$result` will contain the result of the closure. If max attempts are exceeded the inner exception is re-thrown.

You can of course provide other options via the helper method if needed.

Method parameters are `$callback`, `$maxAttempts`, `$strategy`, `$waitCap`, `$useJitter`.

## Retry class usage

The Retry class constructor parameters are `$maxAttempts`, `$strategy`, `$waitCap`, `$useJitter`.

```php
use JBZoo\Retry\Retry;

$retry = new Retry(10, 'exponential', 10000, true);
$result = $retry->run(function() {
    return doSomeWorkThatMightFail();
});
```

Or if you are injecting the Retry class with a dependency container, you can set it up with setters after the fact. Note that setters are chainable.

```php
use JBZoo\Retry\Retry;

// Assuming a fresh instance of $retry was handed to you
$result = (new Retry())
    ->setStrategy('constant')
    ->setMaxAttempts(10)
    ->enableJitter()
    ->run(function() {
        return doSomeWorkThatMightFail();
    });
```

You might want to do this somewhere in your application bootstrap for example. These defaults will be used anytime you create an instance of the Retry class or use the `retry()` helper function.

## Strategies

There are four built-in strategies available: constant, linear, polynomial, and exponential.

The default base time for all strategies is 100 milliseconds.

### Constant

```php
use JBZoo\Retry\Strategies\ConstantStrategy;

$strategy = new ConstantStrategy(500);
```

This strategy will sleep for 500 milliseconds on each retry loop.

### Linear

```php
use JBZoo\Retry\Strategies\LinearStrategy;
$strategy = new LinearStrategy(200);
```

This strategy will sleep for `attempt * baseTime`, providing linear retry starting at 200 milliseconds.

### Polynomial

```php
use JBZoo\Retry\Strategies\PolynomialStrategy;
$strategy = new PolynomialStrategy(100, 3);
```

This strategy will sleep for `(attempt^degree) * baseTime`, so in this case `(attempt^3) * 100`.

The default degree if none provided is 2, effectively quadratic time.

### Exponential

```php
use JBZoo\Retry\Strategies\ExponentialStrategy;
$strategy = new ExponentialStrategy(100);
```

This strategy will sleep for `(2^attempt) * baseTime`.

## Specifying strategy

In our earlier code examples we specified the strategy as a string:

```php
use JBZoo\Retry\Retry;
use function JBZoo\Retry\retry;

retry(function() {
    // ...
}, 10, 'constant');

// OR

$retry = new Retry(10, 'constant');
```

This would use the `ConstantStrategy` with defaults, effectively giving you a 100 millisecond sleep time.

You can create the strategy instance yourself in order to modify these defaults:

```php
use JBZoo\Retry\Retry;
use JBZoo\Retry\Strategies\LinearStrategy;
use function JBZoo\Retry\retry;

retry(function() {
    // ...
}, 10, new LinearStrategy(500));

// OR

$retry = new Retry(10, new LinearStrategy(500));
```

You can also pass in an integer as the strategy, will translate to a ConstantStrategy with the integer as the base time in milliseconds:

```php
use JBZoo\Retry\Retry;
use function JBZoo\Retry\retry;

retry(function() {
    // ...
}, 10, 1000);

// OR

$retry = new Retry(10, 1000);
```

Finally, you can pass in a closure as the strategy if you wish. This closure should receive an integer `attempt` and return a sleep time in milliseconds.

```php
use JBZoo\Retry\Retry;
use function JBZoo\Retry\retry;

retry(function() {
    // ...
}, 10, function($attempt) {
    return (100 * $attempt) + 5000;
});

// OR

$retry = new Retry(10);
$retry->setStrategy(function($attempt) {
    return (100 * $attempt) + 5000;
});
```

## Wait cap

You may want to use a fast growing retry time (like exponential) but then also set a max wait time so that it levels out after a while.

This cap can be provided as the fourth argument to the `retry` helper function, or using the `setWaitCap()` method on the Retry class.

## Jitter

If you have a lot of clients starting a job at the same time and encountering failures, any of the above retry strategies could mean the workers continue to collide at each retry.

The solution for this is to add randomness. See here for a good explanation:

https://www.awsarchitectureblog.com/2015/03/retry.html

You can enable jitter by passing `true` in as the fifth argument to the `retry` helper function, or by using the `enableJitter()` method on the Retry class.

We use the "FullJitter" approach outlined in the above article, where a random number between 0 and the sleep time provided by your selected strategy is used.

## Custom retry decider

By default, Retry will retry if an exception is encountered, and if it has not yet hit max retries.

You may provide your own retry decider for more advanced use cases. Perhaps you want to retry based on time rather than number of retries, or perhaps there are scenarios where you would want retry even when an exception was not encountered.

Provide the decider as a callback, or an instance of a class with an `__invoke` method. Retry will hand it four parameters: the current attempt, max attempts, the last result received, and the exception if one was encountered. Your decider needs to return true or false.

```php
use JBZoo\Retry\Retry;

$retry = new Retry();
$retry->setDecider(function($attempt, $maxAttempts, $result, $exception = null) {
    return someCustomLogic();
});
```

## Error handler callback

You can provide a custom error handler to be notified anytime an exception occurs, even if we have yet to reach max attempts. This is a useful place to do logging for example.

```php
use JBZoo\Retry\Retry;

$retry = new Retry();
$retry->setErrorHandler(function($exception, $attempt, $maxAttempts) {
    Log::error("On run $attempt we hit a problem: " . $exception->getMessage());
});
```


## Unit tests and check code style
```sh
make update
make test-all
```


## License

MIT
