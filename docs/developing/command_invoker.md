# Command invoker service

## How to invoke a command

The `commandInvoker` service can be used to invoke other named commands.

!!! example

    ```php
    $commandInvoker = $this->getContainer()->get('commandInvoker');
    $commandInvoker->invokeCommand($input, 'my:command', ['--option' => 'value']);
    ```

    The above example will invoke the `my:command` command with the specified option.

## Pinning global options

You can pin parameters by using the
`$commandInvoker->pinGlobalOption($option, $value)` method. This will ensure
that subsequent usage of the `commandInvoker` service will pass the pinned
option as a parameter to the command being invoked.

You can unpin an option by calling `$commandInvoker->unpinGlobalOption($option)`.
This will prevent the option from being passed along to future command invocations.

## Interaction with configuration

Configuration is loaded fresh for every command invocation. This means that
config set during runtime will not be available to future command invocations.

!!! example

    ```php
    $this-getConfig()->set('my.foo', 'bar');
    $commandInvoker = $this->getContainer()->get('commandInvoker');
    $commandInvoker->invokeCommand($input, 'my:command');
    ```

    Notice that `my.foo` is set prior to invoking `my:command`. When the
    implementing method for `my:command` is executed and it tries to use
    `my.foo`, it will receive a `null` value because the configuration
    `my.foo` was set during runtime.
