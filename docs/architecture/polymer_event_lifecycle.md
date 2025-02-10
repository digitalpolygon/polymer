# Event Lifecycle

1. Discover extensions.
2. Add extension service providers to the container.
3. Finalize the container.
4. Gather and order config contexts.
   1. Add default context. (handled by ConfigOverlay)
   2. Add polymer context.
   2. Add extension contexts.
   3. Add project context.
   4. Add environment project context.
   5. Add other context placeholders.
   5. Add any other contexts.
   5. Add processor context. (handled by ConfigOverlay)
5. Process loaded configuration.
6. Replace container configuration with newly processed configuration.
7. Hand off execution to runner.
8. Runner registers command file hooks.
9. Runner executes command.
10. Symfony dispatches console.command event.
11. Robo hook events are dispatched. See https://github.com/consolidation/annotated-command?tab=readme-ov-file#hooks
