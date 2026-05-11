<?php

namespace App\Services;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadFileService
{
    public function __construct(
        private readonly ParameterBagInterface $params,

    ){}

    /**
     * @throws \Exception
     */
    public function uploadFile(File $file, $mediaType = "default"): string
    {
        // Vérifie si le type de fichier est valide
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/heic', 'image/heif', 'image/webp', 'application/pdf', 'application/msword', 'application/csv',];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new BadRequestHttpException('Type de fichier non autorisé.');
        }

        // Validation spécifique pour les images de profil
        if (in_array($mediaType, ['UserImages', 'EstablishmentImages'])) {
            $imageMimeTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/heic', 'image/heif', 'image/webp'];
            if (!in_array($file->getMimeType(), $imageMimeTypes)) {
                throw new BadRequestHttpException('Seules les images sont autorisées pour ce type de média.');
            }
        }
        $directory = $this->getDocumentsDirectory($mediaType);
        if (!is_dir($directory)) {
            throw new \Exception("Le dossier '$directory' n'existe pas !");
        }

        // Génère un nom de fichier unique


        $fileName = uniqid('', true) . '.' . $file->guessExtension();

        // Déplace le fichier vers le dossier correspondant
        $file->move($directory, $fileName);

        return $fileName; // Retourne le chemin du fichier pour le stockage en BDD
    }

    public function getDocumentsDirectory($mediaType): string
    {

        // Récupère le chemin du dossier en fonction du type de média
        return match ($mediaType) {
            'UserRegistrationDocuments' => $this->params->get('media_documents_directory'),
            'UserImages' => $this->params->get('media_profile_images_directory') . '/users',
            'EstablishmentImages' => $this->params->get('media_profile_images_directory') . '/establishments',
            'PublicityImages' => $this->params->get('media_publicities_images_directory') . '/pubs',
            default => $this->params->get('private_media_directory'),
        };
    }

//    public function deleteFile(string $getUrl, string $getType): bool
//    {
//        $filePath = $this->getMediaDirectory($getType). DIRECTORY_SEPARATOR. basename($getUrl);
//        if (file_exists($filePath)) {
//            return unlink($filePath);
//        }
//        return false;
//    }
    public function deleteFile(string $fileName, string $mediaType): bool
    {
        $fileDir = $this->getDocumentsDirectory($mediaType) .'/'. $fileName;
        if (file_exists($fileDir)) {
            return unlink($fileDir);
        }
        return false;
    }
}
