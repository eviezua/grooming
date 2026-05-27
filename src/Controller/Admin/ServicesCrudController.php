<?php

namespace App\Controller\Admin;

use App\Entity\Services;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class ServicesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Services::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Service Name'),
            NumberField::new('cost', 'Cost'),
            TimeField::new('default_time', 'Default Duration'),

            ChoiceField::new('status')
                ->setChoices([
                    'Awaiting' => 'Awaiting',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Inactive' => 'Inactive',
                ])
                ->renderAsBadges([
                    'Awaiting' => 'warning',
                    'Approved' => 'success',
                    'Rejected' => 'danger',
                    'Inactive' => 'secondary',
                ]),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')
                ->setChoices([
                    'Awaiting' => Status::Awaiting,
                    'Approved' => Status::Approved,
                    'Rejected' => Status::Rejected,
                    'Inactive' => Status::Inactive,
                ])
            );
    }
}
