<?php
namespace YahnisElsts\PluginUpdateChecker\v5;
class UpdateChecker {
    public function setBranch($branch) {}
    public function setPathInsideRepository($path) {}
}
class PucFactory {
    public static function buildUpdateChecker(...$args) {
        return new UpdateChecker();
    }
}
