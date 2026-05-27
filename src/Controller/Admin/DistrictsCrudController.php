<?php

namespace App\Controller\Admin;

use App\Entity\Districts;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DistrictsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Districts::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name', 'District'),
            AssociationField::new('city', 'City'),
        ];
    }
}
