<?php

class shopWelcomePlugin extends shopPlugin
{
    public function frontendHomepage()
    {
        $message = "Добро пожаловать в наш магазин!";
        return "<div class='welcome-message'>{$message}</div>";
    }
}
