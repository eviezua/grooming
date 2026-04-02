<?php

namespace App\ApiResource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class MasterPhotoApi
{
    #[Assert\NotBlank]
    #[Assert\File(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png'])]
    public ?UploadedFile $file = null;
}