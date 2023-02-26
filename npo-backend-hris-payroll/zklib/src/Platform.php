<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Platform
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function get($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~Platform';

        return $self->_command($command, $command_string);
    }

    /**
     * @param $self
     * @return bool|mixed
     */
    public function getVersion($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~ZKFPVersion';

        return $self->_command($command, $command_string);
    }
}
