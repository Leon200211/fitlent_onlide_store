<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta type="keywords" content="...">
    <meta type="description" content="...">
    <title>Document</title>

    <?php $this->getStyles(); ?>

</head>
<body>
<div class="vg-carcass vg-hide">
    <div class="vg-main">
        <div class="vg-one-of-twenty vg-firm-background-color2  vg-center">
            <a href="<?=PATH?>" target="_blank">
                <div class="vg-element vg-full">
                    <span class="vg-text2 vg-firm-color1">Site</span>
                </div>
            </a>
        </div>
        <div class="vg-element vg-ninteen-of-twenty vg-firm-background-color4 vg-space-between  vg-box-shadow">
            <div class="vg-element vg-third">
                <div class="vg-element vg-fifth vg-center" id="hideButton">
                    <div>
                        <img src="<?=PATH.ADMIN_TEMPLATE?>img/menu-button.png" alt="">
                    </div>
                </div>
                <div class="vg-element vg-wrap-size vg-left vg-search  vg-relative" id="searchButton">
                    <div>
                        <img src="<?=PATH.ADMIN_TEMPLATE?>img/search.png" alt="">
                    </div>
                    <form method="post" action="<?=PATH . \core\base\settings\Settings::get('routes')['admin']['alias']?>/search" autocomplete="off">
                        <input type="text" name="search" class="vg-input vg-text">
                        <div class="vg-element vg-firm-background-color4 vg-box-shadow search_links search_res"></div>
                    </form>
                </div>
            </div>
            <!--кнопка-->
            <a href="<?=PATH . \core\base\settings\Settings::get('routes')['admin']['alias']?>/createsitemap" class="vg-element vg-box-shadow sitemap-button">
                            <span class="vg-text vg-firm-color1">
                                Create sitemap
                            </span>
            </a>
            <!--/кнопка-->
            <div class="vg-element vg-fifth">
                <div class="vg-element vg-half vg-right">
                    <div class="vg-element vg-text vg-center">
                        <span class="vg-firm-color5">admin</span>
                    </div>
                </div>
                <a href="/login/admin/logout/1" class="vg-element vg-half vg-center">
                    <div>
                        <img src="<?=PATH.ADMIN_TEMPLATE?>img/out.png" alt="">
                    </div>
                </a>
            </div>
        </div>
    </div>
    <div class="vg-main vg-right vg-relative">
        <div class="vg-wrap vg-firm-background-color1 vg-center vg-block vg-menu">


            <?php if($this->menu): ?>
                <?php foreach ($this->menu as $table => $item): ?>
                    <a href="<?=$this->adminPath?>show/<?=$table?>" class="vg-wrap vg-element vg-full vg-center <?php if($table === $this->table) echo 'active' ?>">
                        <div class="vg-element vg-half  vg-center">
                            <div>
                                <img src="<?=PATH.ADMIN_TEMPLATE?>img/<?=$item['img'] ? $item['img'] : 'pages.png'?>" alt="pages">
                            </div>
                        </div>
                        <div class="vg-element vg-half vg-center vg_hidden">
                            <span class="vg-text vg-firm-color5"><?=$item['name'] ? $item['name'] : $table?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>


        </div>
