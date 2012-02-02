<?php
namespace Aura\Sql;
use PDOStatement;
use Exception;

class Profiler implements ProfilerInterface
{
    protected $active = false;
    
    protected $profile = [];
    
    // public function __toString()
    // {
    //     $t = 0;
    //     $k = count($this->profile);
    //     
    //     $text = [
    //         "$k queries, {:time} seconds total.",
    //         "",
    //         "========================================",
    //     ];
    //     
    //     foreach ($this->profile as $i => $item) {
    //         $t += $item->time;
    //         $text[] = "#$i of $k ({$item->time}):";
    //         $text[] = "Text: " . var_export($item->text, true);
    //         $text[] = "Data: " . var_export($item->data, true);
    //         $text[] = "Trace: " . $item->info->getTraceAsString;
    //         $text[] = "";
    //         $text[] = "----------------------------------------";
    //     }
    //     
    //     $text[0] = str_replace('{:time}', $t, $text[0]);
    //     return implode(PHP_EOL, $text);
    // }
    
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }
    
    public function isActive()
    {
        return (bool) $this->active;
    }
    
    public function exec(PDOStatement $stmt, array $data = [])
    {
        if (! $this->isActive()) {
            $stmt->execute();
            return;
        }
        
        $before = microtime(true);
        $result = $stmt->execute();
        $after = microtime(true);
        $this->addProfile($stmt->queryString, $before, $after, $data);
        return $result;
    }
    
    public function call($func, $text)
    {
        if (! $this->isActive()) {
            return call_user_func($func);
        }
        
        $before = microtime(true);
        $result = call_user_func($func);
        $after  = microtime(true);
        $this->addProfile($text, $before, $after);
        return $result;
    }
    
    public function addProfile($text, $before, $after, array $data = [])
    {
        $this->profile[] = (object) [
            'text' => $text,
            'time' => $after - $before,
            'data' => $data,
            'info' => new Exception
        ];
    }
    
    public function getProfile()
    {
        return $this->profile;
    }
}