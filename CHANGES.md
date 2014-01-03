- Initial release of 2.0.0-beta1.

Version 2 of Aura.Sql is greatly reduced from version 1, providing only an
ExtendedPDO class, a query profiler, and a connection locator. No query
objects, data mappers, or schema discovery objects are included; these are
available in separate Aura.Sql_* packages. This keeps the package tightly
focused as an extension for PDO, rather than a more general SQL toolset.
