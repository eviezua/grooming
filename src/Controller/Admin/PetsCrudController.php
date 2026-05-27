<?php

namespace App\Controller\Admin;

use App\Entity\Pets;
use App\Enum\Hair;
use App\Enum\Size;
use App\Enum\Species;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class PetsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Pets::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            ChoiceField::new('spice', 'Species')
                ->setChoices(array_combine(
                    array_map(fn($c) => $c->value, Species::cases()),
                    Species::cases()
                )),

            ChoiceField::new('hair', 'Hair')
                ->setChoices(array_combine(
                    array_map(fn($c) => $c->value, Hair::cases()),
                    Hair::cases()
                )),

            TextField::new('breed', 'Breed'),

            ChoiceField::new('size', 'Size')
                ->setChoices(array_combine(
                    array_map(fn($c) => $c->value, Size::cases()),
                    Size::cases()
                )),

            NumberField::new('cost_coficient', 'Cost Coefficient')->setDisabled(),

            AssociationField::new('masters', 'Masters')->autocomplete(),

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
