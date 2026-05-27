<?php

namespace App\Controller\Admin;

use App\Entity\Schedule;
use App\Enum\Weekdays;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ScheduleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Schedule::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield ChoiceField::new('dayOfweek', 'Day of Week')
            ->setChoices([
                'Monday' => Weekdays::Monday,
                'Tuesday' => Weekdays::Tuesday,
                'Wednesday' => Weekdays::Wednesday,
                'Thursday' => Weekdays::Thursday,
                'Friday' => Weekdays::Friday,
                'Saturday' => Weekdays::Saturday,
                'Sunday' => Weekdays::Sunday,
            ]);
        yield TimeField::new('start_time', 'Start Time');
        yield TimeField::new('stop_time', 'Stop Time');
        yield AssociationField::new('master', 'Master')->autocomplete();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Schedules')
            ->overrideTemplate('crud/index', 'admin/crud_index_base.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('master'));
    }

}
