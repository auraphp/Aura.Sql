This release adds new `yield*()` methods to _ExtendedPdo_; these return
iterators to generate one result row at a time, which can reduce memory usage
with very large result sets.
