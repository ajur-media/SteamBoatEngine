<?php

namespace SteamBoat;

class Path
{
    public $atoms = [];
    
    public $isImmutable = false;
    
    public $isTrailingSlash = true;
    
    public $isAbsolutePath = true;
    
    /**
     * Mutable create
     *
     * @param $path
     * @return Path
     */
    public static function create($path)
    {
        return new self($path, false);
    }
    
    /**
     * Immutable create
     *
     * @param $path
     * @return Path
     */
    public static function createImmutable($path)
    {
        return new self($path, true);
    }
    
    /**
     * Path constructor
     *
     * @param $path
     * @param bool $isImmutable
     * @param bool $isTrailingSlash
     */
    public function __construct($path, $isImmutable = false, $isTrailingSlash = true)
    {
        $this->isImmutable = $isImmutable;
        $this->isTrailingSlash = $isTrailingSlash;
        
        if (is_string($path)) {
            $path = trim($path, DIRECTORY_SEPARATOR);
            $this->atoms = explode(DIRECTORY_SEPARATOR, $path);
        } elseif (is_array($path)) {
            $this->atoms = $path;
        }
        
        $this->atoms = $this->trimEach($this->atoms);
    }
    
    /**
     * @return string
     */
    public function toString()
    {
        return
            ($this->isAbsolutePath ? DIRECTORY_SEPARATOR : '')
            .
            implode(DIRECTORY_SEPARATOR, $this->atoms)
            .
            ($this->isTrailingSlash ? DIRECTORY_SEPARATOR : '');
    }
    
    /**
     * @param $data
     * @return $this|Path
     */
    public function join($data)
    {
        $this->isTrailingSlash = true;
        
        if (is_string($data)) {
            $data = explode(DIRECTORY_SEPARATOR, trim($data, DIRECTORY_SEPARATOR));
        }
        $data = $this->trimEach($data);
        
        if ($this->isImmutable) {
            return new self(array_merge($this->atoms, $data), $this->isImmutable, $this->isTrailingSlash);
        }
    
        $this->atoms = array_merge($this->atoms, $data);
        
        return $this;
    }
    
    /**
     * @param $data
     * @return $this|Path
     */
    public function joinName($data)
    {
        $this->isTrailingSlash = false;
        
        $data = $this->trimEach($data);
        
        if ($this->isImmutable) {
            return new self(array_merge($this->atoms, [ $data ]), $this->isImmutable, $this->isTrailingSlash);
        }
        
        $this->atoms = array_merge($this->atoms, [ $data ] );
        
        return $this;
    }
    
    /**
     * apply trim for each element of array
     *
     * @param array|string $data
     * @return string[]|string
     */
    private function trimEach($data)
    {
        if (is_array($data)) {
            return array_map(static function ($item) { return trim($item, DIRECTORY_SEPARATOR); }, $data);
        }
    
        return trim($data);
    }
    
    
}