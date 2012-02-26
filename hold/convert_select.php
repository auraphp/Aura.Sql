<?php

// sqlsrv
public function convertSelect(Select $select)
{
    $limit  = $select->limit;
    $offset = $select->offset;
    
    if (! $limit && ! $offset) {
        // no limit/offset so we can leave it as-is
        return $select->__toString();
    }
    
    if ($limit && ! $offset) {
        // limit, but no offset, so we can use TOP
        $text = $select->__toString();
        $text = preg_replace('/^(SELECT( DISTINCT)?)/', "$1 TOP $limit", $text);
        return $text;
    }
    
    return $this->convertSelectStrategy($select);
}

// sqlsrv
protected function convertSelectStrategy(Select $select)
{
    // limit and offset. a little complicated.
    // first, get the existing order as a string, then remove it.
    $order = $select->getOrderString();
    $select->clearOrder();
    
    // we always need an order for the ROW_NUMBER() OVER(...)
    if (! $order) {
        // always need an order
        $order = '(SELECT 1)';
    }
    
    $start = $select->offset + 1;
    $end   = $select->offset + $select->limit;
    
    return "WITH outertable AS (SELECT *, ROW_NUMBER() OVER (ORDER BY $order) AS __rownum__ FROM (\n"
         . $select->__toString()
         . "\n) AS innertable) SELECT * FROM outertable WHERE __rownum__ BETWEEN $start AND $end";
}

// sqlsrv_denali
protected function convertSelectStrategy(Select $select)
{
    // **MUST** have an ORDER clause to work;
    // in Denali, OFFSET is a sub-clause of the ORDER clause.
    // also, cannot use FETCH without OFFSET.
    return $select->__toString() . "\n"
         . "OFFSET {$select->offset} ROWS\n"
         . "FETCH NEXT {$select->limit} ROWS ONLY"; 
}
