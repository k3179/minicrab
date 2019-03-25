<meta name="viewport" content="width=device-width, initial-scale=1,user-scalable=no">
<?php self::commonCss('/css/fontawesome/css/all.min.css');?>
<?php self::commonCss('/css/common.css');?>
<?php self::css('/css/debug.css');?>
<?php self::commonJs('/js/jquery.js');?>
<?php self::js('/js/debug.js');?>
<?php self::title('Exception: '.$exception['message']);?>

<div class="error-all">
    <div class="error-wrapper">
        <div class="error-main">
            <strong><?php echo $exception['message'];?></strong>
            <div><?php echo '[#0] '.$exception['file'].' : '.$exception['line'];?></div>
        </div>
        <div class="error-detail">
            <?php
            $i =   1;
            foreach($exception['trace'] as $trace){
                if(empty($trace['file'])) continue;
                echo '<div class="error-debug">';
                echo '<div class="error-debug-one">[#'.$i.'] '.$trace['file'].' : '.$trace['line'].'</div>';
                echo '<div class="error-debug-detail">';
                if(!empty($trace['class'])){
                    echo "<div class='error-debug-detail-one'>class : {$trace['class']}</div>";
                }
                if(!empty($trace['function'])){
                    echo "<div class='error-debug-detail-one'>function : {$trace['function']}</div>";
                }
                if(!empty($trace['args'])){
                    $args    =  json_encode($trace['args']);
                    echo "<div class='error-debug-detail-one'>args : {$args}</div>";
                }
                echo '</div>';
                echo '</div>';
                $i++;
            }
            ?>
        </div>
    </div>
</div>