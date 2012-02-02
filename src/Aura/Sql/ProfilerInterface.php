<?php
namespace Aura\Sql;
use PDOStatement;
interface ProfilerInterface
{
    public function setActive($active);
    public function isActive();
    public function exec(PDOStatement $stmt, array $data = []);
    public function call($func, $text);
    public function addProfile($text, $before, $after, array $data = []);
    public function getProfile();
}
