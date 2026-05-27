<?php

namespace App\Controller\Admin;

use App\Entity\Masters;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class MastersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Masters::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name', 'Name'),
            TextField::new('surname', 'Surname'),
            EmailField::new('email', 'Email'),
            TelephoneField::new('phone', 'Phone')->setRequired(false),
            AssociationField::new('id_pets', 'Pets')->onlyOnForms(),
            AssociationField::new('bookings', 'Bookings')->onlyOnForms(),
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Masters')
            ->overrideTemplate('crud/index', 'admin/crud_index_base.html.twig');
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
