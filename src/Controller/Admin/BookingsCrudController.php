<?php

namespace App\Controller\Admin;

use App\Entity\Bookings;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class BookingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Bookings::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('id_master', 'Master');
        yield AssociationField::new('id_client', 'Client');
        yield AssociationField::new('pet', 'Pet');
        yield AssociationField::new('id_services', 'Services');
        yield DateField::new('date');
        yield TimeField::new('time_start');
        yield TimeField::new('time_stop');
        yield ChoiceField::new('status')
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
            ]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Bookings')
            ->overrideTemplate('crud/index', 'admin/crud_index_base.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('id_master'))
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
