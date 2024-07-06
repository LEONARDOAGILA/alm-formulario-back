<?php

namespace App\Http\Controllers\email;

use App\Http\Controllers\Controller;
use App\Http\Resources\RespuestaApi;
use App\Mail\SendMailGraciasPostulacion;
use App\Mail\SendMailTerminosCondiciones;
use App\Mail\SendMailTerminosCondicionesProylecma;
use App\Models\rrhh\Persona;
use Exception;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{

    // Correo para el cliente que solita credito desde la pagina web o desde el formulario institucional
    public function sendEmailTerminosCondiciones($email, $object)
    {
        try {
            // Mail::to($email)->send(new SendMailTerminosCondiciones($object));

            // smtp es el correo default que se utliza para cotizaciones@almespana.com.ec
            Mail::mailer('smtp')
                ->to($email)
                ->send(
                    (new SendMailTerminosCondiciones($object))
                        ->from(
                            config('mail.from.default.address'),
                            config('mail.from.default.name')
                        )
                );

            return response()->json(RespuestaApi::returnResultado('success', 'Correo electrónico enviado correctamente a ' . $email, ''));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error al enviar el correo', $e->getMessage()));
        }
    }

    // Correo para el usuario que se postulando en el formulario trabaja con nosotros
    public function sendEmailGraciasPostulacion($object)
    {
        try {
            $persona = Persona::where('id', $object->id_persona)->first();

            if ($persona) {
                // Mail::to($persona->email)->send(new SendMailGraciasPostulacion($object));

                // smtp es el correo default que se utliza para cotizaciones@almespana.com.ec
                Mail::mailer('smtp')
                    ->to($persona->email)
                    ->send(
                        (new SendMailGraciasPostulacion($object))
                            ->from(
                                config('mail.from.default.address'),
                                config('mail.from.default.name')
                            )
                    );
            }

            return response()->json(RespuestaApi::returnResultado('success', 'Correo electrónico enviado correctamente a ' . $persona->email, ''));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error al enviar el correo', $e->getMessage()));
        }
    }


    // Correo para el cliente que solita credito desde la pagina web o desde el formulario institucional
    public function sendEmailTerminosCondicionesProylecma($email, $object)
    {
        try {
            // Mail::to($email)->send(new SendMailTerminosCondiciones($object));

            // smtp es el correo default que se utliza para cotizaciones@almespana.com.ec
            Mail::mailer('correo_proylecma')
                ->to($email)
                ->send(
                    (new SendMailTerminosCondicionesProylecma($object))
                        ->from(
                            config('mail.from.proylecma.address'),
                            config('mail.from.proylecma.name')
                        )
                );

            return response()->json(RespuestaApi::returnResultado('success', 'Correo electrónico enviado correctamente a ' . $email, ''));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error al enviar el correo', $e->getMessage()));
        }
    }


    public function sendEmailPruebaAlmacenesEspana()
    {
        try {
            $email = 'juanjgsj@gmail.com';

            // 734 Id de un registro de la tabla crm.solicitudes_creditos_web, es para poder poner TRUE el campo confirmacion_terminos_condiciones

            $object = (object) [
                'asunto' => 'AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y DE RIESGO CREDITICIO ENTRE EL CLIENTE Y ALMESPANA CIA. LTDA',
                'linkAutorizar' => 'https://almacenesespana.ec/prueba2/ecommerce-back/public/api/ecommerce/autorizarTratamientoDatos/' . 'DIPOR' . '/' . 734,
            ];

            // esta linea antes se ocupaba porque solo enviamos del correo cotizaciones
            // Mail::to($email)->send(new SendMailTerminosCondiciones($object));

            // smtp es el correo default que se utliza para cotizaciones@almespana.com.ec
            Mail::mailer('smtp')
                ->to($email)
                ->send(
                    (new SendMailTerminosCondiciones($object))
                        ->from(
                            config('mail.from.default.address'),
                            config('mail.from.default.name')
                        )
                );

            return response()->json(RespuestaApi::returnResultado('success', 'Correo electrónico enviado correctamente a ' . $email, ''));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error al enviar el correo', $e->getMessage()));
        }
    }

    public function sendEmailPruebaProylecma()
    {
        try {
            $email = 'juanjgsj@gmail.com';

            // 734 Id de un registro de la tabla crm.solicitudes_creditos_web, es para poder poner TRUE el campo confirmacion_terminos_condiciones

            $object = (object) [
                'asunto' => 'AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y DE RIESGO CREDITICIO ENTRE EL CLIENTE Y PROYLECMA',
                'linkAutorizar' => 'https://almacenesespana.ec/prueba2/ecommerce-back/public/api/ecommerce/autorizarTratamientoDatos/' . 'DIPOR' . '/' . 734,
            ];

            Mail::mailer('correo_proylecma')
                ->to($email)
                ->send(
                    (new SendMailTerminosCondiciones($object))
                        ->from(
                            config('mail.from.proylecma.address'),
                            config('mail.from.proylecma.name')
                        )
                );

            return response()->json(RespuestaApi::returnResultado('success', 'Correo electrónico enviado correctamente a ' . $email, ''));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error al enviar el correo', $e->getMessage()));
        }
    }

}
