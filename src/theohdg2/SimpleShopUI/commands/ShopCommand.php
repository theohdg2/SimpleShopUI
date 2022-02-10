<?php

namespace theohdg2\SimpleShopUI\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ShopCommand extends Command
{
    public function __construct()
    {
        parent::__construct("shop","open shop ui",$this->getUsage(),[]);
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if($sender instanceof Player){

        }else{
            $sender->sendMessage("execute this command in game");
        }
    }
}