<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaire = Questionnaire::where(
            'name',
            'Encuesta de Situación Social'
        )->firstOrFail();

        $version = SurveyVersion::where([
            'questionnaire_id' => $questionnaire->id,
            'version' => '1.0',
        ])->firstOrFail();

        $section = Section::where([
            'survey_version_id' => $version->id,
            'name' => 'I. Información General de la Encuesta',
        ])->firstOrFail();

        $types = QuestionType::pluck('id', 'code');

        $questions = [
            [
                'code' => 'GENERAL_001',
                'label' => 'Seleccione fecha de la encuesta',
                'type' => 'date',
                'required' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'GENERAL_002',
                'label' => 'Seleccione el nombre de la comunidad',
                'description' => 'Dato geográfico: departamento, municipio, caserío o aldea, colonia o barrio.',
                'type' => 'text',
                'required' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'GENERAL_003',
                'label' => 'En caso de que el nombre no aparezca en el listado. Por favor escríbalo aquí',
                'description' => 'Campo para registrar el nombre de la comunidad cuando no aparece en el listado de la pregunta anterior.',
                'type' => 'text',
                'required' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'GENERAL_004',
                'label' => '¿Qué tipo de inmueble se encuentra en el predio?',
                'type' => 'single_choice',
                'required' => true,
                'sort_order' => 4,
                'options' => [
                    'vivienda' => 'Vivienda',
                    'local_comercial' => 'Local comercial',
                    'vivienda_local_comercial' => 'Vivienda y local comercial',
                    'iglesia' => 'Iglesia',
                    'edificacion_construccion' => 'Edificación en construcción',
                    'centro_educativo_gubernamental' => 'Centro Educativo Gubernamental',
                    'centro_educativo_privado' => 'Centro Educativo Privado',
                    'centro_comunitario' => 'Centro Comunitario',
                    'salud_gubernamental' => 'Establecimiento de Salud Gubernamental',
                    'salud_privado' => 'Establecimiento de Salud Privado',
                    'infraestructura_deportiva' => 'Infraestructura deportiva',
                    'posta_policial_militar' => 'Posta policial/militar',
                    'parque_plaza_publica' => 'Parque o plaza pública',
                    'solar_baldio' => 'Solar baldío',
                    'no_identificado' => 'No identificado',
                    'otro' => 'Otro',
                ],
            ],
            [
                'code' => 'GENERAL_005',
                'label' => '¿Qué tipo de evento natural causó este daño en su vivienda?',
                'type' => 'single_choice',
                'required' => true,
                'sort_order' => 5,
                'options' => [
                    'inundacion' => 'Inundación',
                    'deslizamiento_tierra' => 'Deslizamiento de tierra',
                    'huracan_lluvias_fuertes' => 'Huracán/lluvias fuertes',
                    'derrumbe' => 'Derrumbe',
                    'vientos_fuertes' => 'Vientos fuertes',
                    'socavon' => 'Socavón',
                    'incendio' => 'Incendio',
                    'caida_rocas' => 'Caída de rocas',
                    'otros' => 'Otros',
                    'ninguno' => 'NINGUNO',
                ],
            ],
            [
                'code' => 'GENERAL_006',
                'label' => 'Especifique',
                'type' => 'text',
                'required' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'GENERAL_007',
                'label' => 'Escriba el nombre del inmueble',
                'description' => 'En viviendas particulares este campo puede no ser aplicable.',
                'type' => 'text',
                'required' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'GENERAL_008',
                'label' => '¿Qué tipo de local comercial es?',
                'type' => 'text',
                'required' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'GENERAL_009',
                'label' => '¿El inmueble presenta daños visibles?',
                'description' => 'Pregunta de observación.',
                'type' => 'single_choice',
                'required' => true,
                'sort_order' => 9,
                'options' => [
                    'sin_danos' => 'No se observan daños',
                    'danos_visibles' => 'Sí se observan daños',
                    'en_ruinas' => 'En ruinas',
                ],
            ],
            [
                'code' => 'GENERAL_010',
                'label' => 'Tome una fotografía clara del inmueble',
                'description' => 'En posición horizontal y que se aprecie el predio que esté a la par. Subir archivo menor de 10 MB.',
                'type' => 'image',
                'required' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'GENERAL_011',
                'label' => 'Tome una fotografía clara del inmueble — otro ángulo',
                'description' => 'Subir archivo menor de 10 MB.',
                'type' => 'image',
                'required' => false,
                'sort_order' => 11,
            ],
            [
                'code' => 'GENERAL_012',
                'label' => 'Tome una fotografía clara del inmueble — otros elementos relevantes',
                'description' => 'Subir archivo menor de 10 MB.',
                'type' => 'image',
                'required' => false,
                'sort_order' => 12,
            ],
            [
                'code' => 'GENERAL_013',
                'label' => '¿Puede continuar con la encuesta?',
                'type' => 'single_choice',
                'required' => true,
                'sort_order' => 13,
                'options' => [
                    'hay_informante' => 'Sí, hay informante',
                    'informante_no_localizado' => 'No, informante no localizado',
                    'informante_se_nego' => 'Informante se negó a dar información',
                    'presenta_riesgo' => 'No, presenta riesgo',
                ],
            ],
            [
                'code' => 'GENERAL_014',
                'label' => 'Condición de ocupación del inmueble',
                'description' => 'Una fuente secundaria puede ser un vecino, una autoridad de la comunidad, un empleado o cuidador de la vivienda.',
                'type' => 'single_choice',
                'required' => true,
                'sort_order' => 14,
                'options' => [
                    'ocupado' => 'Una fuente secundaria informa que está Ocupado',
                    'desocupado' => 'Una fuente secundaria informa que está Desocupado',
                    'no_identificado' => 'No identificado',
                ],
            ],
        ];

        foreach ($questions as $data) {
            $options = $data['options'] ?? [];

            unset($data['options']);

            $question = Question::updateOrCreate(
                [
                    'section_id' => $section->id,
                    'code' => $data['code'],
                ],
                [
                    'question_type_id' => $types[$data['type']],
                    'label' => $data['label'],
                    'description' => $data['description'] ?? null,
                    'required' => $data['required'],
                    'active' => true,
                    'sort_order' => $data['sort_order'],
                ]
            );

            $this->syncOptions($question, $options);
        }
    }

    private function syncOptions(
        Question $question,
        array $options
    ): void {
        foreach ($options as $value => $label) {
            $question->options()->updateOrCreate(
                [
                    'value' => $value,
                ],
                [
                    'label' => $label,
                    'sort_order' => array_search(
                        $value,
                        array_keys($options),
                        true
                    ) + 1,
                    'active' => true,
                ]
            );
        }
    }
}