<?php
/**
 * An implementation for asterisk call spool.
 *
 * PHP Version 5
 *
 * @category   Pagi
 * @package    CallSpool
 * @subpackage Impl
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://marcelog.github.com/PAGI/ Apache License 2.0
 * @version    SVN: $Id$
 * @link       http://marcelog.github.com/PAGI/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
namespace PAGI\CallSpool\Impl;

use PAGI\CallSpool\ICallSpool;
use PAGI\CallSpool\CallFile;
use PAGI\CallSpool\Exception\CallSpoolException;

/**
 * An implementation for asterisk call spool.
 *
 * PHP Version 5
 *
 * @category   Pagi
 * @package    CallSpool
 * @subpackage Impl
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://marcelog.github.com/PAGI/ Apache License 2.0
 * @link       http://marcelog.github.com/PAGI/
 */
class CallSpoolImpl implements ICallSpool
{
    /**
     * Where to temporary generate call files.
     * @var string
     */
    private $_tmpDir = '/tmp';

    /**
     * Asterisk spool directory.
     * @var string
     */
    private $_spoolDir = '/var/spool/asterisk';

    /**
     * Current instance.
     * @var CallSpoolImpl
     */
    private static $_instance = false;

    /**
     * Returns an instance for this spool/
	 *
     * @param string[] $options Configuration options.
     *
     * @return CallSpoolImpl
     */
    public static function getInstance(array $options = array())
    {
        if (self::$_instance === false) {
            $ret = new CallSpoolImpl($options);
            self::$_instance = $ret;
        } else {
            $ret = self::$_instance;
        }
        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see PAGI\CallSpool.ICallSpool::spool()
     */
    public function spool(CallFile $call, $schedule = false)
    {
        $filename = tempnam($this->_tmpDir, 'PAGICallFile');
        if ($filename === false) {
            throw new CallSpoolException('Could generate temporary filename');
        }
        if (@file_put_contents($filename, $call->serialize()) === false) {
            @unlink($filename);
            throw new CallSpoolException('Error writing: ' . $filename);
        }
        if ($schedule !== false) {
            if (@touch($filename, $schedule) === false) {
                @unlink($filename);
                throw new CallSpoolException('Error scheduling: ' . $filename);
            }
        }
        $newFilename = implode(
            DIRECTORY_SEPARATOR,
            array($this->_spoolDir, 'outgoing', basename($filename))
        );
        if (@rename($filename, $newFilename) === false) {
            @unlink($filename);
            throw new CallSpoolException('Error spooling: ' . $newFilename);
        }
        return $newFilename;
    }

    /**
     * Constructor.
     *
     * @param string[] $options Options for this spool.
     *
     * @return void
     */
    private function __construct(array $options)
    {
        if (isset($options['tmpDir'])) {
            $this->_tmpDir = $options['tmpDir'];
        }
        if (isset($options['spoolDir'])) {
            $this->_spoolDir = $options['spoolDir'];
        }
    }
}