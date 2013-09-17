- [NEW] Query\Sqlite\(Select|Insert|Update|Delete) classes to support
  SQLite-specific behaviors

- [NEW] Query\Pgsql\(Select|Insert|Update|Delete) classes to support
  PostgreSQL-specific behaviors

- [REF] Refactor existing limit/offset behaviors to Query\LimitTrait and Query\OffsetTrait

- [REF] Refactor existing order-by behaviors to Query\OrderByTrait

- [REF] Refactor query-string indenting and comma-separation behaviors to new
  methods 

- [ADD] Methods on each db-specific connection object to return db-specific
  query objects; e.g., Connection\Mysql::newMysqlSelect(),
  Connection\Pgsql::newPgsqlInsert(), etc.

Many thanks to @MAXakaWIZARD for his work on the features in this release.
