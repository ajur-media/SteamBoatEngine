<?php

namespace SteamBoat;

/**
 * Class Path
 *
 * Data ALWAYS immutable
 *
 * @package SteamBoat
 */
class Path
{
    public $atoms = [];
    
    public $isTrailingSlash = true;
    
    public $isAbsolutePath = true;
    
    /**
     * Create (immutable)
     *
     * @param $path
     * @param bool $isTrailingSlash
     * @param bool $isAbsolutePath
     * @return Path
     */
    public static function create($path, $isTrailingSlash = true, $isAbsolutePath = true)
    {
        return new self($path, $isTrailingSlash, $isAbsolutePath);
    }
    
    /**
     * Path constructor
     *
     * @param $path
     * @param bool $isTrailingSlash
     * @param bool $isAbsolutePath
     */
    public function __construct($path, $isTrailingSlash = true, $isAbsolutePath = true)
    {
        $this->isTrailingSlash = $isTrailingSlash;
        $this->isAbsolutePath = $isAbsolutePath;
        
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
     * Magic __toString method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
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
        
        return new self(array_merge($this->atoms, $data), $this->isTrailingSlash, $this->isAbsolutePath);
    }
    
    /**
     * @param $data
     * @return $this|Path
     */
    public function joinName($data)
    {
        $this->isTrailingSlash = false;
        
        $data = $this->trimEach($data);
    
        return new self(array_merge($this->atoms, [ $data ]), $this->isTrailingSlash, $this->isAbsolutePath);
    }
    
    /**
     *
     * @return bool
     */
    public function isPresent():bool
    {
        return is_dir($this->toString());
    }
    
    /**
     *
     * @param int $access_rights
     * @return bool
     */
    public function isAccessible($access_rights = 0777):bool
    {
        $path = $this->toString();
    
        return is_dir( $path ) || ( mkdir( $path, 0777, true ) && is_dir( $path ) );
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