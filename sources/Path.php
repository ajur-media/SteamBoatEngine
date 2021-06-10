<?php

namespace SteamBoat;

class Path
{
    public $atoms = [];
    
    public $isImmutable = false;
    
    /**
     * Factory ?
     *
     * @param $path
     * @return Path
     */
    public static function create($path)
    {
        return new self($path, false);
    }
    
    public static function createImmutable($path)
    {
        return new self($path, true);
    }
    
    public function __construct($path, $isImmutable = false)
    {
        $this->isImmutable = $isImmutable;
        
        if (is_string($path)) {
            $path = trim($path, DIRECTORY_SEPARATOR);
            $this->atoms = explode(DIRECTORY_SEPARATOR, $path);
        } elseif (is_array($path)) {
            $this->atoms = $path;
        }
        
        $this->atoms = $this->trimEach($this->atoms);
    }
    
    public function toString()
    {
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $this->atoms) . DIRECTORY_SEPARATOR;
    }
    
    public function join($data)
    {
        if (is_string($data)) {
            $data = explode(DIRECTORY_SEPARATOR, trim($data, DIRECTORY_SEPARATOR));
        }
        $data = $this->trimEach($data);
        
        if ($this->isImmutable) {
            return new self(array_merge($this->atoms, $data), $this->isImmutable);
        }
    
        $this->atoms = array_merge($this->atoms, $data);
        return $this;
    }
    
    /**
     * apply trim for each element of array
     *
     * @param $data
     * @return array|string[]
     */
    private function trimEach($data)
    {
        return array_map(static function ($item) { return trim($item, DIRECTORY_SEPARATOR); }, $data);
    }
    
    
}