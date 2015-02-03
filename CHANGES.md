- ADD: ExtendedPdo::disconnect() method to close connections explicitly. This does not work for injected PDO connection objects, which should be managed from their creation point, not as part of ExtendedPdo. Thanks to both Jacob Emerick and Jacques Woodcock for their initial implementations.

- CHG: ExtendedPdo::bindValue() now throws Exception\CannotBindValue when it encounters a non-bindable value. This helps with debugging values that make their way down to the PDO layer, which PDO cannot bind.
