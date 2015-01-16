- SEC: ExtendedPdo no longer enables self::ATTR_EMULATE_PREPARES by default; this is to avoid security holes when using emulation.

- REF: Extract the statement-rebuilding logic to its own Rebuilder class

- ADD: ExtendedPdo::fetchGroup() functionality.

- ADD: When binding values via perform(), add the self::PARAM_* type based on the value being bound.

- TST: Update testing structure
