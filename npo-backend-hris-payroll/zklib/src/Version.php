<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Version
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function get($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_VERSION;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}
