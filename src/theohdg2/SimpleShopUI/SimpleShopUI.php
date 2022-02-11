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

    protected function onEnable(): void
    {
        if(($api = Server::getInstance()->getPluginManager()->getPlugin("SimpleMoneyAPI")) instanceof Plugin){
            $this->moneyAPI = $api;
        }else{
            //TODO trad ans link
            $this->getServer()->getLogger()->alert("please install dependence: SimpleMoneyAPI (here link)");
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
                if($this->getConfig()->get("quit-message-admin-onenabled",false)){
                    $player->sendMessage($this->getConfig()->get("quit-admin-message",""));
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
                case 6:
                       $player->sendForm($this->getAccueilPlayerForm());
                    break;
            }
        });
        //TODO trad

        $form->setTitle("Admin shop configurateur");
        //////SECTION CATEGORY/////////////////////////
        $form->addButton("Créer une catégory");
        $form->addButton("Editer une catégory");
        $form->addButton("Supprimé une category");
        //////SECTION ITEM/////////////////////////
        $form->addButton("Créer une item");
        $form->addButton("Editer une item");
        $form->addButton("Supprimé une item");

        $form->addButton("voir le rendu");
        return $form;
    }
    /////////CATEGORY////////////////
    public function getCreateCategoryForm(): Form{
        $allc = $this->getAllCategory($this->getShop()->getAll(),null,true,"->");
        $form = new CustomForm(function(Player $player,array $data = null) use ($allc){
            if($data === null){
                if($this->getConfig()->get("quit-message-admin-onenabled",false)){
                    $player->sendMessage($this->getConfig()->get("quit-admin-message",""));
                }
                return;
            }
            $apres = str_replace("->",".",$allc[$data[0]]);
            $name = $data[1];
            $imgToogle = $data[2]??false;
            $imgType = $data[3] === false ? 0 : 1;
            $imgPath = $data[4] ?? "";
            $this->getShop()->set($apres.$name,
            [
                "img" => [
                    "type" => $imgType,
                    "path" => $imgPath
                ]
            ]);
            $this->getShop()->save();
        });
        //TODO trad
        $form->setTitle("Admin shop create category");
        $form->addDropdown("mettre apres ",$allc);
        $form->addInput("nom de la category");
        $form->addToggle("ajouter une image");
        $form->addToggle("si celui dessus est cocher l'image va etre un texture/url");
        $form->addInput("texture/url complet (pour la texture: textures/items/apple) laissez vide si vous ne voulez pas d'image");
        return $form;
    }

    public function getAllCategory(array $array,string $path = null,bool $indexIsNumber = false,string $separator = "."){
        $return = [];
        foreach ($array as $name => $data){
            if(($data["identifierofcategoryoritemsell"]??-1) === self::CATEGORY){
                if($path === null){
                    $path = $name;
                }else{
                    $path = $path.$separator.$name;
                }
                if($indexIsNumber === true){
                    $return[] = $path;
                }else{
                    $return[$name] = $path;
                }
                unset($data["identifierofcategoryoritemsell"]);
                foreach ($this->getAllCategory($data,$path) as $name => $returns){
                    if($indexIsNumber === true){
                        $return[] = $returns;
                    }else{
                        $return[$name] = $returns;
                    }
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
                if($this->getConfig()->get("quit-message-onenabled",false)){
                    $player->sendMessage($this->getConfig()->get("quit-message",""));
                }
                return;
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
            if($datas[$btn]["identifierofcategoryoritemsell"] ?? -1 === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }elseif($datas[$btn]["identifierofcategoryoritemsell"] ??-1 === self::CATEGORY){
                $form->addButton($btn,$datas[$btn]["img"]["type"] ?? -1,$datas[$btn]["img"]["path"] ?? "");
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
            if($name === "identifierofcategoryoritemsell" || !isset($data["identifierofcategoryoritemsell"])) continue;
            $allBtn[] = $name;
            $datas[$name] = $data;
        }


        $form = new SimpleForm(function(Player $player,int $name = null) use ($categoryPath,$decomponse,$allBtn,$datas){
           if($name === null){
               if($this->getConfig()->get("quit-message-onenabled",false)){
                   $player->sendMessage($this->getConfig()->get("quit-message",""));
               }
               return;
           }
            $data = $datas[$allBtn[$name]] ?? null;
            if($data === null){
                //TODO message
                $player->sendMessage("une erreur est survenue");
            }
            if(($data["identifierofcategoryoritemsell"]??-1) === self::ITEM){
                $player->sendForm($this->getBuyForm($data));
            }elseif(($data["identifierofcategoryoritemsell"]??-1) === self::CATEGORY){
                $player->sendForm($this->getCategoryForm($categoryPath."/".$allBtn[$name]));
            }

        });
        //TODO trad
        $form->setTitle("SimpleShop ".$decomponse[array_key_last($decomponse)]);
        foreach ($allBtn as $name => $btn){
            if(($datas[$btn]["identifierofcategoryoritemsell"] ?? -1) === self::ITEM){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
            }elseif(($datas[$btn]["identifierofcategoryoritemsell"] ??-1) === self::CATEGORY){
                $form->addButton($btn,$datas[$btn]["image_type"] ?? -1,$datas[$btn]["image_link"] ?? "");
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
               return;
           }
           $item = ItemFactory::getInstance()->get($data["id"],$data["meta"],$option[1]);
           if($item instanceof Item){
               $item->setLore($data["lores"] ?? []);
               $item->setCustomName($data["custom_name"]);
               if($player->getInventory()->canAddItem($item)){
                   if($this->getMoneyAPI()->reduceMoney($player->getName(),$data["price_for_min_count"]??1)){
                   $player->getInventory()->addItem($item);
                   //TODO trad
                       $player->sendMessage("vous venez d'acheter ".$item->getCustomName()??$item->getName()."x".$option[1]);
                   }else{
                       //TODO trad
                       $player->sendMessage("vous n'avez pas assez de money");
                   }
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

        $form->addLabel($data["price_for_min_count"]." pour ".$data["max_count"]);
        $form->addSlider("nombre",$data["min_count"],$data["max_count"],1);

        return $form;
    }



}