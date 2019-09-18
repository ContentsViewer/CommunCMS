<?php
require_once(MODULE_DIR . "/ContentsDatabaseManager.php");
require_once(MODULE_DIR . "/Authenticator.php");

$vars['rootContentPath'] = ContentsDatabaseManager::DefalutRootContentPath();
$vars['showRootChildren'] = false;

if(!isset($vars['owner']) || $vars['owner'] === false){
    // ownerが設定されていないとき
    // ログインユーザのページを使う
    // 無ければ, defalutを使う
    
    if(isset($vars['loginedUser']) && $vars['loginedUser'] !== false 
       && Authenticator::GetUserInfo($vars['loginedUser'], 'contentsFolder', $contentsFolder)){
        $vars['rootContentPath'] = $contentsFolder . '/' . ROOT_FILE_NAME;
        $vars['showRootChildren'] = true; // すでにログイン済み
    }
    else{
        $isAuthorized = true;
        $isPublic = true;

        $owner = Authenticator::GetFileOwnerName($vars['rootContentPath']);
        if($owner !== false){
            Authenticator::GetUserInfo($owner, 'isPublic', $isPublic);
        }
        
        if (!$isPublic) {
            // セッション開始
            //  どこからこのスクリプトが呼ばれても動作するように
            @session_start();
            if (Authenticator::GetLoginedUsername() !== $owner) {
                $isAuthorized = false;
            }
        }

        if($isPublic || $isAuthorized){
            $vars['showRootChildren'] = true;
        }
    }
}

if(isset($vars['owner']) && $vars['owner'] !== false){
    $vars['rootContentPath'] = $vars['contentsFolder'] . '/' . ROOT_FILE_NAME;
    $vars['showRootChildren'] = false;
        
    if(isset($vars['isPublic']) && $vars['isPublic']){
        $vars['showRootChildren'] = true;
    }
    if(isset($vars['isAuthorized']) && $vars['isAuthorized']){
        $vars['showRootChildren'] = true;
    }
}

if(!isset($vars['errorMessage'])) $vars['errorMessage'] = '';