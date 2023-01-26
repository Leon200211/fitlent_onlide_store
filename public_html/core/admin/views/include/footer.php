        </div><!--.vg-main.vg-right-->
    </div><!--.vg-carcass-->

<!-- Блок ошибок-->
        <div class="vg_modal vg-center">
            <?php
            if(isset($_SESSION['res']['answer'])){
                echo $_SESSION['res']['answer'];
                unset($_SESSION['res']);
            }
            ?>
        </div>

        <script>
            const PATH = '<?=PATH?>'
            const ADMIN_MODE = '1'
        </script>
        <?php $this->getScripts(); ?>

</body>
</html>
