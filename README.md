# PHP Backoff

Easily wrap your code with retry functionality. This library provides:

 1. 4 backoff strategies (plus the ability to use your own)
 2. Optional jitter / randomness to spread out retries and minimize collisions
 3. Wait time cap

## Installation

```
composer require stechstudio/backoff
```

## Defaults

This library provides sane defaults so you can hopefully just jump in for most of your use cases.

By default the backoff is quadratic with a 100ms base time (`attempt^2 * 100`), a max of 5 retries, and no jitter.

## Quickstart

The simplest way to use Backoff is with the global `backoff` helper function:

```
$result = backoff(function() {
    return doSomeWorkThatMightFail();
});
```

If successful `$result` will contain the result of the closure. If max attempts are exceeded the inner exception is re-thrown.

You can of course provide other options via the helper method if needed. 

Method parameters are `$callback`, `$maxAttempts`, `$strategy`, `$waitCap`, `$useJitter`.

## Backoff class usage
 
The Backoff class constructor parameters are `$maxAttempts`, `$strategy`, `$waitCap`, `$useJitter`.

```
$backoff = new Backoff(10, 'exponential', 10000, true);
$result = $backoff->run(function() {
    return doSomeWorkThatMightFail();
});
```

Or if you are injecting the Backoff class with a dependency container, you can set it up with setters after the fact. Note that setters are chainable.

```
// Assuming a fresh instance of $backoff was handed to you
$result = $backoff
    ->setStrategy('constant')
    ->setMaxAttempts(10)
    ->enableJitter()
    ->run(function() {
        return doSomeWorkThatMightFail();
    });
```

## Changing defaults

If you find you want different defaults, you can modify them via static class properties:

```
Backoff::$defaultMaxAttempts = 10;
Backoff::$defaultStrategy = 'exponential';
Backoff::$defaultJitterEnabled = true;
```

You might want to do this somewhere in your application bootstrap for example. These defaults will be used anytime you create an instance of the Backoff class or use the `backoff()` helper function.

## Strategies

There are four built-in strategies available: constant, linear, polynomial, and exponential.

The default base time for all strategies is 100 milliseconds.

### Constant

```
$strategy = new ConstantStrategy(500);
```

This strategy will sleep for 500 milliseconds on each retry loop.

### Linear

```
$strategy = new LinearStrategy(200);
```

This strategy will sleep for `attempt * baseTime`, providing linear backoff starting at 200 milliseconds.

### Polynomial

```
$strategy = new PolynomialStrategy(100, 3);
```

This strategy will sleep for `(attempt^degree) * baseTime`, so in this case `(attempt^3) * 100`.

The default degree if none provided is 2, effectively quadratic time.

### Exponential

```
$strategy = new ExponentialStrategy(100);
```

This strategy will sleep for `(2^attempt) * baseTime`.

## Specifying strategy

In our earlier code examples we specified the strategy as a string:

```
backoff(function() {
    ...
}, 10, 'constant');

// OR

$backoff = new Backoff(10, 'constant');
```

This would use the `ConstantStrategy` with defaults, effectively giving you a 100 millisecond sleep time.

You can create the strategy instance yourself in order to modify these defaults:

```
backoff(function() {
    ...
}, 10, new LinearStrategy(500));

// OR

$backoff = new Backoff(10, new LinearStrategy(500));
```

You can also pass in an integer as the strategy, will translates to a ConstantStrategy with the integer as the base time in milliseconds:

```
backoff(function() {
    ...
}, 10, 1000);

// OR

$backoff = new Backoff(10, 1000);
```

Finally, you can pass in a closure as the strategy if you wish. This closure should receive an integer `attempt` and return a sleep time in milliseconds.

```
backoff(function() {
    ...
}, 10, function($attempt) {
    return (100 * $attempt) + 5000; 
});

// OR

$backoff = new Backoff(10);
$backoff->setStrategy(function($attempt) {
    return (100 * $attempt) + 5000;
});
```

## Wait cap

You may want to use a fast growing backoff time (like exponential) but then also set a max wait time so that it levels out after a while.

This cap can be provided as the fourth argument to the `backoff` helper function, or using the `setWaitCap()` method on the Backoff class.

## Jitter

If you have a lot of clients starting a job at the same time and encountering failures, any of the above backoff strategies could mean the workers continue to collide at each retry.
 
The solution for this is to add randomness. See here for a good explanation:

https://www.awsarchitectureblog.com/2015/03/backoff.html

You can enable jitter by passing `true` in as the fifth argument to the `backoff` helper function, or by using the `enableJitter()` method on the Backoff class.
 
We use the "FullJitter" approach outlined in the above article, where a random number between 0 and the sleep time provided by your selected strategy is used.
