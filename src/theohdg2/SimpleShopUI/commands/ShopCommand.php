<?php

namespace theohdg2\SimpleShopUI\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use theohdg2\SimpleShopUI\SimpleShopUI;

class ShopCommand extends Command{
    public function __construct(){
        parent::__construct("shop","open shop ui","/shop",[]);
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($sender instanceof Player){
            $sender->sendForm(SimpleShopUI::getInstance()->getAccueilAdminForm());
        }else{
            $sender->sendMessage("execute this command in game");
        }
    }
}