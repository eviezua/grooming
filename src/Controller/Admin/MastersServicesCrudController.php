<?php

namespace App\Controller\Admin;

use App\Entity\MastersServices;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class MastersServicesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MastersServices::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('master', 'Master')->autocomplete();
        yield AssociationField::new('service', 'Service')->autocomplete();
        yield MoneyField::new('price')->setCurrency('UAH')->setStoredAsCents(false)->setRequired(true);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'MastersServices')
            ->overrideTemplate('crud/index', 'admin/crud_index_base.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('master'))
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
