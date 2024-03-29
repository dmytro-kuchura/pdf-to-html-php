<?php

namespace Kuchura\PdfToHtml;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class who can handle about converting pdf file to Html files
 * Please reserve an unprotected pdf files or uncopyrighted pdf files
 * @author Mochamad Gufron
 * @link   http://github.com/mgufrone
 * @since  v1.0
 *
 */

class Base
{
    private $options = [
        'singlePage'   => false,
        'imageJpeg'    => false,
        'ignoreImages' => false,
        'zoom'         => 1.5,
        'noFrames'     => true,
    ];
    public  $outputDir;
    private $bin     = "/usr/bin/pdftohtml";
    private $file;

    /**
     * open pdf file and set convert configuration options
     *
     * @param string $pdfFile path to pdf file
     * @param array $options  configuration for converting
     *
     * @return $this current object
     */
    public function __construct($pdfFile = '', $options = [])
    {
        if (empty($pdfFile))
            return $this;
        $pdf = $this;
        if (!empty($options))
            array_walk($options, function ($value, $key) use ($pdf) {
                $pdf->setOptions($key, $value);
            });

        return $this->open($pdfFile);
    }

    /**
     * open pdf file that will be converted. make sure it is exists
     *
     * @param string $pdfFile path to pdf file
     *
     * @return $this current object
     */
    public function open($pdfFile)
    {
        $this->file = $pdfFile;
        $this->setOutputDirectory(dirname($pdfFile));

        return $this;
    }

    /**
     * generating html files using pdftohtml software.
     * @return $this current object
     */
    public function generate()
    {
        $args = [
            $this->outputDir,
            '/',
            preg_replace("/\.pdf$/", "", basename($this->file)),
            '.html'
        ];

        $output = implode('', $args);
       
        $options = $this->generateOptions();

       
        if (PHP_OS === 'WINNT') {
            $command = '"'.$this->bin().'" '.$options.' "'.$this->file.'" "'.$output.'"';
        }
        else {
            $command = $this->bin()." ".$options." '".$this->file."' '".$output."'";
        }
        exec($command);

        return $this;
    }

    /**
     * generate options based on the preserved options
     * @return string options that will be passed on running the command
     */
    public function generateOptions()
    {
        $generated = [];
        array_walk($this->options, function ($value, $key) use (&$generated) {
            $result = "";
            switch ($key) {
                case "singlePage":
                    $result = $value ? "-c" : "-s";
                break;
                case "imageJpeg":
                    $result = "-fmt ".($value ? "jpg" : "png");
                break;
                case "zoom":
                    $result = "-zoom ".$value;
                break;
                case "ignoreImages":
                    $result = $value ? "-i" : "";
                break;
                case 'noFrames':
                    $result = $value ? '-noframes' : '';
                break;
            }
            $generated[] = $result;
        });

        return implode(" ", $generated);
    }

    /**
     * change value of preserved configuration
     *
     * @param string $key  key of option you want to change
     * @param mixed $value value of option you want to change
     *
     * @return $this current object
     */
    public function setOptions($key, $value)
    {
        if (isset($this->options[ $key ]))
            $this->options[ $key ] = $value;

        return $this;
    }

    /**
     * open pdf file that will be converted. make sure it is exists
     *
     * @param string $pdFile path to pdf file
     *
     * @return $this current object
     */
    public function setOutputDirectory($dir)
    {
        $this->outputDir = $dir;

        return $this;
    }

    /**
     * clear the whole files that has been generated by pdftohtml. Make sure directory ONLY contain generated files from pdftohtml
     * because it remove the whole contents under preserved output directory
     * @return $this current object
     */
    public function clearOutputDirectory()
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->outputDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = (string)$file;
            $basename = basename($path);
            if ($basename != '..' && $basename != ".gitignore") {
                if (is_file($path) && file_exists($path))
                    unlink($path);
                elseif (is_dir($path) && file_exists($path))
                    rmdir($path);
            }
        }

        return $this;
    }

    public function bin()
    {
        return Config::get('pdftohtml.bin', '/usr/bin/pdftohtml');
    }
}
