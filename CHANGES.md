First stable release of Aura.Sql 2.0.0

Version 2 of Aura.Sql is greatly reduced from version 1, providing only an
ExtendedPDO class, a query profiler, and a connection locator. No query
objects, data mappers, or schema discovery objects are included; these are
available in separate Aura.Sql_* packages. This keeps the package tightly
focused as an extension for PDO, rather than a more general SQL toolset.

This is a breaking change from the beta. Previously, the ExtendedPdo object itself would retain values to bind against the next query. After discussion with interested parties, notably Rasmus Schultz, I was convinced that it was too much of a departure from normal PDO semantics.

Thus, the collection of values for binding has been removed. The methods query(), exec(), and prepare() no longer take bound values directly. Instead,we have a new method perform() that acts like query() but takes an array of values to bind at query time. We also have a new method prepareWithValues() that prepares a statement and binds values at that time. Finally, the new method fetchAffected() acts like exec(), but with bind values passed at the time of calling (just like with the other fetch*() methods).

In addition, you can now pass an existing PDO connection to ExtendedPdo so decorate that existing PDO instance with the ExtendedPdo behaviors.

Thanks to ralouphie, koriym, jblotus, and stof for their fixes and improvements in this release. Thanks also to Stan Lemon for the new proxy/decorator behavior, Rasmus Schultz for his semantic insights, and (as always) to Hari KT for his continued attention to detail.

The full list of changes follows.

- [BRK] Remove methods bindValue(), bindValues(), and getBindValues()

- [BRK] query(), exec(), and prepare() no longer bind values; perform() and prepareWithValues() do

- [FIX] setAttribute() needs to return a bool; thanks @mindplay-dk

- [FIX] PDO::quote() now converts NULL to ''; this fix honors the normal PDO behavior.

- [FIX] Method rollBack() now returns a boolean result

- [ADD] Extract PdoInterface from ExtendedPdoInterface

- [ADD] Add fetchObject*() to ExtendedPdoInterface

- [ADD] Add methods perform() and prepareWithValues() to bind values as part of the call

- [ADD] Add method fetchAffected() as an exec()-with-values replacement

- [CHG] Constructor is now polymorphic; inject an existing PDO instance to be decorated, or pass PDO params

- [ADD] Add method getPdo() to return the proxied/decorated instance

