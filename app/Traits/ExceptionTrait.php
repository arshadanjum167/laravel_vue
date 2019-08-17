<?php
namespace App\Traits;
use App\Traits\ApiResponser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Response;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
trait ExceptionTrait {

    use ApiResponser;

    public function returnExeption($req,$exep){

        if($exep instanceof ModelNotFoundException){
            return $this->isModel($req,$exep);
        }
        if ($exep instanceof NotFoundHttpException) {
            return $this->isHttp($req,$exep);
        }
        if($exep instanceof MethodNotAllowedHttpException){
            return $this->isMethod($req,$exep);
        }
        if($exep instanceof ValidationException){
            return $this->isValidation($req,$exep);
        }
        if($exep instanceof QueryException){
            return $this->isQuery($req,$exep);
        }
        if($exep instanceof FatalThrowableError){
            return $this->isFatal($req,$exep);
        }
        if($exep instanceof FatalErrorException){
            return $this->isFatalError($req,$exep);
        }
        if($exep instanceof AuthorizationException){
            return $this->isAuthorized($req,$exep);
        }
        //for all other exception
        if($exep instanceof HttpException){
            return $this->isOther($req,$exep);
        }
        return parent::render($req,$exep);
    }

    protected function isModel($req,$exep){
        // $model = last(explode('\\',$exep->getModel()));
        $model = class_basename($exep->getModel());
        return $this->ErrorResponse($message="$model not found",$status = 403);

    }
    protected function isHttp($req,$exep){
        return $this->ErrorResponse($message="API endpoint is not found",$status = 404);
    }

    protected function isMethod($req,$exep){
        return $this->ErrorResponse($message="This method is not allowed",$status = 404);
    }

    protected function isAuthorized($req,$exep){
        return $this->ErrorResponse($message=$exep->getMessage(),$status = 403);
    }

    protected function isValidation($req,$exep){
        return $this->ErrorResponse($message="Bad Request",$status = 404);
    }
    protected function isQuery($req,$exep){
        if(config('app.debug')){
            return $this->ErrorResponse($message=$exep->getMessage(),$status = 404);
        }else
        return $this->ErrorResponse($message="Something went wrong!",$status = 404);
    }
    protected function isFatal($req,$exep){
            $message = $exep->getMessage().' on line number '.$exep->getLine().': '.$exep->getFile();
        return $this->ErrorResponse($message=$message,$status = 404);
    }
    protected function isFatalError($req,$exep){
            $message = $exep->getMessage().' on line number '.$exep->getLine();
        return $this->ErrorResponse($message=$message,$status = 404);
    }
    protected function isOther($req,$exep){
        return $this->ErrorResponse($message=$exep->getMessage(),$exep->getStatusCode());
    }

    protected function unauthenticated($request, AuthenticationException $exception){

            return $request->expectsJson()
                    ? $this->ErrorResponse($message="Unauthenticated",$status = 401)
                    : '';
                    // redirect()->guest(route('authentication.index'))
    }

}
?>
