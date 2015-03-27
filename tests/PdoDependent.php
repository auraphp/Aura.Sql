<?php
namespace Aura\Sql;

use PDO;

class PdoDependent
{
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchAll()
    {
        $stm = 'SELECT * FROM pdotest';
        $sth = $this->pdo->prepare($stm);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
