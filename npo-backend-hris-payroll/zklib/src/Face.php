<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Face
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function on($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = 'FaceFunOn';

        return $self->_command($command, $command_string);
    }
}
