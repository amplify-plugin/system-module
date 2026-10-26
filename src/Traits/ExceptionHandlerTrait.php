<?php

namespace Amplify\System\Traits;

use Amplify\System\Exceptions\SystemException;
use Amplify\System\Mail\ExceptionReportMail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait ExceptionHandlerTrait
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Not Found.'], 404);
            }
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {

            // Return JSON for API / AJAX requests
            if ($this->shouldReturnJson($request, $exception)) {
                return response()->json(
                    ['message' => __('Security Token Expired. Please refresh the page and try again.')],
                    419);
            }

            // Optional: fallback for web requests
            return redirect()
                ->back()
                ->withInput()
                ->with([
                    'alert' => true,
                    'error' => __('Security Token Expired. Please refresh the page and try again.'),
                ]);
        }

        return parent::render($request, $exception);
    }

    /**
     * Write code on Method
     * @throws SystemException
     */
    private function notifyException(Throwable $throwable): void
    {
        try {

            if (app()->environment('production')) {

                $support_mails = config('amplify.developer.bug_recipient', []);

                if (!empty($support_mails)) {

                    $content['message'] = $throwable->getMessage();
                    $content['file'] = $throwable->getFile();
                    $content['line'] = $throwable->getLine();
                    $content['trace'] = $throwable->getTrace();
                    $content['previous'] = $throwable->getPrevious();
                    $content['url'] = request()->fullUrl();
                    $content['body'] = request()->all();
                    $content['ip'] = (app()->runningInConsole()) ? 'console' : request()->ip();
                    $content['title'] = get_class($throwable);

                    Mail::to($support_mails)->send(new ExceptionReportMail($content));

                }
            }
        } catch (Throwable $exception) {

            throw new SystemException($throwable->getMessage(), 0, $exception);
        }
    }

    /**
     * @param $request
     * @param AuthenticationException $exception
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->shouldReturnJson($request, $exception)
            ? response()->json(['message' => 'You need to be logged in to access this feature.'], 401)
            : redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
