<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Device
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function name($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~DeviceName';

        return $self->_command($command, $command_string);
    }

    /**
     * @param $self
     * @return bool|mixed
     */
    public function enable($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_ENABLE_DEVICE;
        $command_string = '';

        return $self->_command($command, $command_string);
    }

    /**
     * @param $self
     * @return bool|mixed
     */
    public function disable($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DISABLE_DEVICE;
        $command_string = chr(0) . chr(0);

        return $self->_command($command, $command_string);
    }
}
