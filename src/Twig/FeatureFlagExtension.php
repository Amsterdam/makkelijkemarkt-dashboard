<?php

namespace App\Twig;

use App\Security\User;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('has_feature', [$this, 'hasFeatureFlagEnabled']),
        ];
    }

    public function hasFeatureFlagEnabled(string $featureFlag)
    {
        /**
         * @var User $user
         */
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        return $user->hasFeatureFlagEnabled($featureFlag);
    }
}
