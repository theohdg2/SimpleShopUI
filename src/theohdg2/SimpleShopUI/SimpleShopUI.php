<?php

namespace theohdg2\SimpleShopUI;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\Form;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use theohdg2\SimpleShopUI\Commands\ShopCommand;

class SimpleShopUI extends PluginBase{

    private static SimpleShopUI $instance;

    public const CATEGORY = 2;
    public const ITEM = 1;

    private Config $config;
    private Config $shop;

    protected function onEnable(): void
    {
        //define the insatnce of this
        self::$instance = $this;
        //create folder
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder()."Language");
        //save config.yml
        $this->saveDefaultConfig();
        $this->saveResource("shop.json");
        //save all lang configuration file
        $scan = scandir($this->getFile()."resources/Language");
        array_shift($scan);
        array_shift($scan);
        foreach ($scan as $file){
            $this->saveResource("Language/".$file);
        }
        //define the instance of config
        $this->config = new Config($this->getDataFolder()."config.yml",Config::YAML);
        $this->shop = new Config($this->getDataFolder()."shop.json",Config::JSON);
        //register command
        $this->getServer()->getCommandMap()->register("simpleshopui",new ShopCommand());
    }

    /**
     * @return SimpleShopUI
     */
    public static function getInstance(): SimpleShopUI
    {
        return self::$instance;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getShop(): Config
    {
        return $this->shop;
    }


    ///////////API\FORM//////////////

    //////////ADMIN SECTION//////////
    public function getAccueilAdminForm():Form{}
    /////////CATEGORY////////////////
    public function getCreateCategoryForm(): Form{}
    public function getDeleteCategoryForm(): Form{}
    public function getEditCategoryForm(): Form{}
    ////////////ITEM//////////////////
    public function getaddItemForm(): Form{}
    public function getDeleteItemForm(): Form{}
    public function getEditItemForm(): Form{}

    //////////PLAYER SECTION//////////
    public function getAccueilPlayerForm(): Form{
        $allBtn = [];
        $datas = [];
        foreach ($this->getShop()->getAll() as $name => $data){
            $allBtn[] = $name;
            $datas[$name] = $data;
        }

        $form = new SimpleForm(function(Player $player,int $btnSelect = null) use ($allBtn,$datas){
            if($btnSelect === null){
                if($this->getConfig()->get("quit-message-onenabled",false)){
                    $player->sendMessage($this->getConfig()->get("quit-message",""));
                }
            }
            $btnSelect = $allBtn[$btnSelect] ?? null;
            $data = $datas[$btnSelect] ?? null;
            if($data === null || $btnSelect === null){
                //TODO message
                $player->sendMessage("une erreur est survenue");
            }
            if($data["identifierofcategoryoritemsell"] === self::ITEM){
                $player->sendForm($this->getBuyForm($data));
            }elseif($data["identifierofcategoryoritemsell"] === self::CATEGORY){
                $player->sendForm($this->getCategoryForm($btnSelect));
            }else{
                //TODO trad
                $player->sendMessage("un container non identifier à été detecté");
            }

        });
        //TODO: mettre les trads
        $form->setTitle("SimpleShopUI");
        foreach ($allBtn as $btn){
            if($datas[$btn]["identifierofcategoryoritemsell"] === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }else {
                $form->addButton($btn);
            }
        }

        return $form;
    }

    public function getCategoryForm(string $categoryPath): Form{
        $decomponse = explode("/",$categoryPath);

        $allBtn = [];
        $datas = [];
        foreach ($this->getShop()->getAll() as $name => $data){
            $allBtn[] = $name;
            $datas[$name] = $data;
        }


        $form = new SimpleForm(function(Player $player,int $data = null) use ($categoryPath,$decomponse,$allBtn,$datas){
           if($data === null){
               if($this->getConfig()->get("quit-message-onenabled",false)){
                   $player->sendMessage($this->getConfig()->get("quit-message",""));
               }
           }
            $btnSelect = $allBtn[$btnSelect] ?? null;
            $data = $datas[$btnSelect] ?? null;
            if($data === null || $btnSelect === null){
                //TODO message
                $player->sendMessage("une erreur est survenue");
            }
            if($data["identifierofcategoryoritemsell"] === self::ITEM){
                $player->sendForm($this->getBuyForm($data));
            }elseif($data["identifierofcategoryoritemsell"] === self::CATEGORY){
                $player->sendForm($this->getCategoryForm($btnSelect));
            }else{
                //TODO trad
                $player->sendMessage("un container non identifier à été detecté");
            }

        });
        //TODO trad
        $form->setTitle("SimpleShop ".$decomponse[array_key_last($decomponse)]);
        foreach ($allBtn as $btn){
            if($datas[$btn]["identifierofcategoryoritemsell"] === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }else {
                $form->addButton($btn);
            }
        }


        return $form;
    }


    public function getBuyForm(array $data): Form{
        $form = new CustomForm(function (Player $player,array $option = null) use ($data){
           if($option === null){
               if($this->getConfig()->get("quit-message-onenabled",false)){
                   $player->sendMessage($this->getConfig()->get("quit-message",""));
               }
           }
           $item = ItemFactory::getInstance()->get($data["id"],$data["meta"],$option[1]);
           if($item instanceof Item){
               $item->setLore($data["lores"] ?? []);
               $item->setCustomName($data["custom_name"]);
               if($player->getInventory()->canAddItem($item)){
                   //TODO mettre l'api d'economy
                   $player->getInventory()->addItem($item);
               }else{
                   //TODO trad
                   $player->sendMessage("vous n'avez pas assez de place dans votre inventaire");
               }
           }else{
               //TODO trad
               $player->sendMessage($data['id'].":".$data['meta'].". n'est pas un item");
           }

        });
        //TODO trad
        $form->setTitle("Acheter ".$data["custom_name"] ?? "cette item");

        $form->addLabel($data["price_for_max_count"]." pour ".$data["max_count"]);
        $form->addSlider("nombre",$data["min_count"],$data["max_count"],1);

        return $form;
    }



}