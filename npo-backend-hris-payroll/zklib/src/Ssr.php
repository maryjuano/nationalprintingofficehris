<?php

namespace Local\Zklib\ZK;

use ZKLib;

class Ssr
{
    /**
     * @param $self
     * @return bool|mixed
     */
    public function get($self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~SSR';

        return $self->_command($command, $command_string);
    }
}
