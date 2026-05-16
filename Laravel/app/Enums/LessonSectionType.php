<?php

namespace App\Enums;

enum LessonSectionType: string
{
    case Introduction = 'introduction';
    case KeyConcepts = 'key_concepts';
    case ConcreteExample = 'concrete_example';
    case Summary = 'summary';
}
