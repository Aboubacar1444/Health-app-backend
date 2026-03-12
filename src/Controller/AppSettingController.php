<?php

namespace App\Controller;

use App\Entity\AppSetting;
use App\Repository\AppSettingRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Utils\DataType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\VarDumper\Cloner\Data;

#[Route('/settings')]
class AppSettingController extends AbstractController
{
    public function __construct(
        private readonly AppSettingRepository $settingRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_settings', methods: ['GET'])]
    public function getAllSettings(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->settingRepository->createQueryBuilder('s')
            ->orderBy('s.key', 'ASC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['app_setting']);
    }

    #[Route('/public', name: 'get_public_settings', methods: ['GET'])]
    public function getPublicSettings(): JsonResponse
    {
        $settings = $this->settingRepository->findBy(['isPublic' => true]);
        
        $body = json_decode($this->serializer->serialize($settings, 'json', ["groups" => "app_setting"]), true);
        return $this->responsesService->successResponse($body, "Paramètres publics récupérés");
    }

    #[Route('/{key}', name: 'get_setting_by_key', methods: ['GET'])]
    public function getSettingByKey(string $key): JsonResponse
    {
        $setting = $this->settingRepository->findOneBy(['key' => $key]);
        
        if (!$setting) {
            return $this->responsesService->errorResponse("Paramètre introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($setting, 'json', ["groups" => "app_setting"]), true);
        return $this->responsesService->successResponse($body, "Paramètre trouvé");
    }

    #[Route('', name: 'create_setting', methods: ['POST'])]
    public function createSetting(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }
        $data['dataType'] = DataType::tryFrom($data['dataType']) ?? DataType::STRING;
        $setting = $this->serializer->deserialize(json_encode($data), AppSetting::class, 'json');
        $setting = $this->entityHelper->save($setting);

        $body = json_decode($this->serializer->serialize($setting, 'json', ["groups" => "app_setting"]), true);
        return $this->responsesService->successResponse($body, "Paramètre créé avec succès");
    }

    #[Route('/{id}', name: 'update_setting', methods: ['PUT'])]
    public function updateSetting(string $id, Request $request): JsonResponse
    {
        $setting = $this->settingRepository->find($id);
        if (!$setting) {
            return $this->responsesService->errorResponse("Paramètre introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            if ($key === 'dataType') {
                $value = DataType::tryFrom($value) ?? DataType::STRING;
            }
            $setter = 'set' . ucfirst($key);
            if (method_exists($setting, $setter)) {
                $setting->$setter($value);
            }
        }

        $setting = $this->entityHelper->update($setting);
        $body = json_decode($this->serializer->serialize($setting, 'json', ["groups" => "app_setting"]), true);
        return $this->responsesService->successResponse($body, "Paramètre mis à jour");
    }

    #[Route('/{id}', name: 'delete_setting', methods: ['DELETE'])]
    public function deleteSetting(string $id): JsonResponse
    {
        $setting = $this->settingRepository->find($id);
        if (!$setting) {
            return $this->responsesService->errorResponse("Paramètre introuvable");
        }

        $this->entityHelper->remove($setting);
        return $this->responsesService->successResponse([], "Paramètre supprimé");
    }
}