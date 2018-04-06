<?php
namespace mcore;

class upload
{
    private $options = [
        //file type
        'type' => '*',
        
        //max size limit in bytes(1M=1048576)
        'max_size' => 1000000,

    ];

    public function __construct($options=[])
    {
        if (isset($options['type'])) {
            $this->options['type'] = $options['type'];
        }
        if (isset($options['max_size'])) {
            $this->options['max_size'] = $options['max_size'];
        }
    }
    
    private function uploadFilter($file)
    {
        if ($file['size']>$this->options['max_size']) {
            exit("Error: file size out of the max size limit");
        }
    }

    public function up($file,$to)
    {
        $this->uploadFilter($file);
        
        try{
            $r = @move_uploaded_file($file['file'], $to);
            if (!$r) {
                throw new \Exception('Error: failed to move task file');
            }

        }
        catch (\Exception $e) {
            exit($e->getMessage());
        }

        return true;
    }

}

