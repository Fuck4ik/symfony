<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Workflow\MarkingStore;

use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;

/**
 * MethodMarkingStore stores the marking with a subject's method.
 *
 * This store deals with a "single state" or "multiple state" Marking.
 *
 * "single state" Marking means a subject can be in one and only one state at
 * the same time. Use it with state machine.
 *
 * "multiple state" Marking means a subject can be in many states at the same
 * time. Use it with workflow.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class MethodMarkingStore implements MarkingStoreInterface
{
    private bool $singleState;
    private string $property;

    /**
     * @param string $property Used to determine methods to call
     *                         The `getMarking` method will use `$subject->getProperty()`
     *                         The `setMarking` method will use `$subject->setProperty(string|array $places, array $context = array())`
     */
    public function __construct(bool $singleState = false, string $property = 'marking')
    {
        $this->singleState = $singleState;
        $this->property = $property;
    }

    public function getMarking(object $subject): Marking
    {
        $method = 'get'.ucfirst($this->property);

        if (!method_exists($subject, $method)) {
            throw new LogicException(sprintf('The method "%s::%s()" does not exist.', get_debug_type($subject), $method));
        }

        $marking = null;
        try {
            $marking = $subject->{$method}();
        } catch (\Error $e) {
            $unInitializedPropertyMessage = sprintf('Typed property %s::$%s must not be accessed before initialization', get_debug_type($subject), $this->property);
            if ($e->getMessage() !== $unInitializedPropertyMessage) {
                throw $e;
            }
        }

        if (null === $marking) {
            return new Marking();
        }

        if ($this->singleState) {
            $result = new Marking();
            $result->mark($marking);

            return $result;
        }

        if (!\is_array($marking)) {
            throw new LogicException(sprintf('The method "%s::%s()" did not return an array and the Workflow\'s Marking store is instantiated with $singleState=false.', get_debug_type($subject), $method));
        }

        $result = new Marking();
        foreach ($marking as $place => $nbTokenOrEnum) {
            if ($nbTokenOrEnum instanceof \UnitEnum) {
                $result->mark($nbTokenOrEnum);
            } else {
                $result->mark($place);
            }
        }

        return $result;
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $enumPlaces = $marking->getEnumPlaces();
        if ([] !== $enumPlaces) {
            if ($this->singleState) {
                $marking = array_values($enumPlaces)[0];
            } else {
                $marking = $enumPlaces;
            }
        } else {
            $marking = $marking->getPlaces();
            if ($this->singleState) {
                $marking = key($marking);
            }
        }

        $method = 'set'.ucfirst($this->property);

        if (!method_exists($subject, $method)) {
            throw new LogicException(sprintf('The method "%s::%s()" does not exist.', get_debug_type($subject), $method));
        }

        $subject->{$method}($marking, $context);
    }
}
