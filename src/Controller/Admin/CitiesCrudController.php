<?php

namespace App\Controller\Admin;

use App\Entity\Cities;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class CitiesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cities::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('city', 'City'),

            ChoiceField::new('status')
                ->setChoices([
                    'Awaiting' => Status::Awaiting,
                    'Approved' => Status::Approved,
                    'Rejected' => Status::Rejected,
                    'Inactive' => Status::Inactive,
                ])
                ->renderAsBadges([
                    Status::Awaiting->value => 'warning',
                    Status::Approved->value => 'success',
                    Status::Rejected->value => 'danger',
                    Status::Inactive->value => 'secondary',
                ]),

            AssociationField::new('masters', 'Masters')
                ->hideOnForm()
                ->onlyOnDetail(),

            AssociationField::new('districts', 'Districts')
                ->hideOnForm()
                ->onlyOnDetail(),
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
