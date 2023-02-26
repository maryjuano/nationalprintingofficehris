<?php

namespace Local\Zklib\ZK;

use ZKLib;

class SerialNumber
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function get($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~SerialNumber';

        return $self->_command($command, $command_string);
    }
}
