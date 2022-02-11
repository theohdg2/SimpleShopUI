<?php

namespace theohdg2\SimpleShopUI;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\Form;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use theohdg2\SimpleShopUI\Commands\ShopCommand;

class SimpleShopUI extends PluginBase{

    private static SimpleShopUI $instance;

    public const CATEGORY = 2;
    public const ITEM = 1;

    private Config $config;
    private Config $shop;
    private Plugin $moneyAPI;
    private string $prefix;

    protected function onEnable(): void
    {
        if(($api = Server::getInstance()->getPluginManager()->getPlugin("SimpleMoneyAPI")) instanceof Plugin){
            $this->moneyAPI = $api;
        }else{
            $this->getServer()->getLogger()->alert($this->getConfigLanguage()->get("missing-pluging"));
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

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

        var_dump($this->getAllCategory($this->getShop()->getAll()));

        //Get SimpleShopUI Prefix
        $this->prefix = $this->getConfig()->get("prefix");
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

    /**
     * @return Plugin
     */
    public function getMoneyAPI(): Plugin
    {
        return $this->moneyAPI;
    }

    public function getConfigLanguage(): Config{
        return new Config($this->getDataFolder()."Language/lang_".$this->getConfig()->get("default-lang","eng").".yml",Config::YAML);
    }


    ///////////API\FORM//////////////

    //////////ADMIN SECTION//////////
    public function getAccueilAdminForm():Form{
        $form = new SimpleForm(function(Player $player,int $data = null){
            if($data === null){
                if($this->getConfig()->get("confirm-message",false)){
                    $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("form-close-admin",""));
                }
                return;
            }
            switch ($data){
                case 0:
                    $player->sendForm($this->getCreateCategoryForm());
                    break;
                case 1:
                    $player->sendForm($this->getEditCategoryForm());
                    break;
                case 2:
                    $player->sendForm($this->getDeleteCategoryForm());
                    break;
                case 3:
                    $player->sendForm($this->getAddItemForm());
                    break;
                case 4:
                    $player->sendForm($this->getEditItemForm());
                    break;
                case 5:
                    $player->sendForm($this->getDeleteItemForm());
                    break;
            }
        });
        $form->setTitle($this->getConfigLanguage()->getNested("form-admin.home.title"));
        //////SECTION CATEGORY/////////////////////////
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.category.create"));
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.category.edit"));
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.category.delete"));
        //////SECTION ITEM/////////////////////////
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.item.create"));
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.item.edit"));
        $form->addButton($this->getConfigLanguage()->getNested("form-admin.home.item.delete"));
        return $form;
    }
    /////////CATEGORY////////////////
    public function getCreateCategoryForm(): Form{
        $form = new CustomForm(function(Player $player,array $data = null){
            if($data === null){
                if($this->getConfig()->get("confirm-message",false)){
                    $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("form-close-admin",""));
                }
                return;
            }
            
        });
        $form->setTitle($this->getConfigLanguage()->getNested("form-admin.create.title"));
        $form->addDropdown($this->getConfigLanguage()->getNested("form-admin.create.after"),$this->getAllCategory($this->getShop()->getAll()));
        $form->addInput($this->getConfigLanguage()->getNested("form-admin.create.categoryName"));
        $form->addToggle($this->getConfigLanguage()->getNested("form-admin.create.image"));
        $form->addToggle($this->getConfigLanguage()->getNested("form-admin.create.choice"));
        $form->addInput($this->getConfigLanguage()->getNested("form-admin.create.url"));
        return $form;
    }

    public function getAllCategory(array $array){
        $return = [];
        foreach ($array as $name => $data){
            if(($data["identifierofcategoryoritemsell"]??-1) === self::CATEGORY){
                $return[$name] = $name;
                unset($data["identifierofcategoryoritemsell"]);
                foreach ($this->getAllCategory($data) as $returns){
                    $return[$returns] = $returns;
                }
            }
        }
        return $return;
    }

    public function getDeleteCategoryForm(): Form{}
    public function getEditCategoryForm(): Form{}
    ////////////ITEM//////////////////
    public function getAddItemForm(): Form{}
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
                if($this->getConfig()->get("confirm-message",false)){
                    $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("form-close-admin"));
                }
                return;
            }
            $btnSelect = $allBtn[$btnSelect] ?? null;
            $data = $datas[$btnSelect] ?? null;
            if($data === null || $btnSelect === null){
                $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("error"));
            }
            if($data["identifierofcategoryoritemsell"] === self::ITEM){
                $player->sendForm($this->getBuyForm($data));
            }elseif($data["identifierofcategoryoritemsell"] === self::CATEGORY){
                $player->sendForm($this->getCategoryForm($btnSelect));
            }else{
                $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("error-container"));
            }
        });
        $form->setTitle($this->getConfigLanguage()->get("form-name"));
        foreach ($allBtn as $btn){
            if($datas[$btn]["identifierofcategoryoritemsell"] ?? -1 === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }elseif($datas[$btn]["identifierofcategoryoritemsell"] ??-1 === self::CATEGORY){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }
        }

        return $form;
    }

    public function getCategoryForm(string $categoryPath): Form{
        $decomponse = explode("/",$categoryPath);

        $allBtn = [];
        $datas = [];

        $pathArray = [];
        for ($i = 0; $i < count($decomponse);$i++){
            $pathArray = $pathArray[$decomponse[$i]] ?? $this->getShop()->getAll()[$decomponse[0]];
        }
        foreach ($pathArray as $name => $data){
            $allBtn[] = $name;
            $datas[$name] = $data;
        }


        $form = new SimpleForm(function(Player $player,int $data = null) use ($categoryPath,$decomponse,$allBtn,$datas){
           if($data === null){
               if($this->getConfig()->get("confirm-message",false)){
                   $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("form-close-admin"));
               }
               return;
           }
            $btnSelect = $allBtn[$data] ?? null;
            $data = $datas[$btnSelect] ?? null;
            if($data === null || $btnSelect === null){
                $player->sendMessage($this->getConfigLanguage()->get("error"));
            }
            if($data["identifierofcategoryoritemsell"] === self::ITEM){
                $player->sendForm($this->getBuyForm($data));
            }elseif($data["identifierofcategoryoritemsell"] === self::CATEGORY){
                $player->sendForm($this->getCategoryForm($categoryPath."/".$btnSelect));
            }else{
                $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("error-container"));
            }

        });
        $form->setTitle($this->getConfigLanguage()->get("form-name").$decomponse[array_key_last($decomponse)]);
        foreach ($allBtn as $btn){
            if($datas[$btn]["identifierofcategoryoritemsell"] ?? -1 === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }elseif($datas[$btn]["identifierofcategoryoritemsell"] ??-1 === self::CATEGORY){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }
        }
        return $form;
    }

    public function getBuyForm(array $data): Form{
        $form = new CustomForm(function (Player $player,array $option = null) use ($data){
           if($option === null){
               if($this->getConfig()->get("confirm-message",false)){
                   $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("form-close-admin"));
               }
               return;
           }
           $item = ItemFactory::getInstance()->get($data["id"],$data["meta"],$option[1]);
           if($item instanceof Item){
               $item->setLore($data["lores"] ?? []);
               $item->setCustomName($data["custom_name"]);
               if($player->getInventory()->canAddItem($item)){
                   if($this->getMoneyAPI()->reduceMoney($player->getName(),$data["price_for_min_count"]??1)){
                   $player->getInventory()->addItem($item);
                       $player->sendMessage($this->prefix.str_replace(["{item}","{count}"],[$item->getCustomName()??$item->getName(),$option[1]],$this->getConfigLanguage()->get("confirm-buy")));
                   }else{
                       $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("error-buy"));
                   }
               }else{
                   $player->sendMessage($this->prefix.$this->getConfigLanguage()->get("out-of-storage"));
               }
           }else{
               $player->sendMessage($this->prefix.str_replace(["{item}","{meta}"],[$data['id'],$data['meta']],$this->getConfigLanguage()->get("error-item")));
           }
        });
        $form->setTitle(str_replace("{item}",$data["custom_name"],$this->getConfigLanguage()->get("form-confirm-buy2")) ?? $this->getConfigLanguage()->get("form-confirm-buy"));
        $form->addLabel(str_replace(["{count}","{price}"],[$data["price_for_min_count"],$data["max_count"]],$this->getConfigLanguage()->get("form-buy-label")));
        $form->addSlider($this->getConfigLanguage()->get("form-number"),$data["min_count"],$data["max_count"],1);
        return $form;
    }

    //TODO: create a verif for world player (ban world to execute the command)

}