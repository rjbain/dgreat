Config Import - Delete Entities
===============================

This module allows you to automatically delete entities that may otherwise stop
a configuration import because their bundle/entity type is being deleted.

This can be very helpful when this is part of an automated process for
deployment.

The module also has a "debug" mode, which would list the entity type:ID when
they are detected, even if automated deletion is disabled. This makes it easier
to delete them or at least review them before deciding what to do with them.