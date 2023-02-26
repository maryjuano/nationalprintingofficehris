<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Os
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function get($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~OS';

        return $self->_command($command, $command_string);
    }
}
