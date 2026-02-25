import { Head, useForm } from "@inertiajs/react";
import { useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldError,
} from "@/components/ui/field";

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
        remember: true,
    });

    // Clear password field when errors occur
    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            setData("password", "");
        }
    }, [errors]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post("/login");
    };

    return (
        <>
            <Head title="Login" />

            <div className="fixed inset-0 grid lg:grid-cols-2">
                {/* Left Side - School Image/Branding */}
                <div className="hidden lg:flex relative bg-primary items-center justify-center overflow-hidden">
                    {/* School Image */}
                    <img
                        src="/hero-image-login.jpg"
                        alt="School"
                        className="absolute inset-0 w-full h-full object-cover blur-sm"
                    />
                    {/* Subtle overlay for better text readability */}
                    <div className="absolute inset-0 bg-gradient-to-br from-black/40 via-black/30 to-black/40"></div>

                    {/* Content overlay */}
                    <div className="relative z-10 max-w-2xl space-y-6 text-center px-8">
                        <div>
                            <h1 className="text-4xl font-bold text-white mb-4">
                                School Registration System
                            </h1>
                            <p className="text-lg text-white/90">
                                Manage your school's student registrations,
                                payments, and academic records all in one place.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Right Side - Login Form */}
                <div className="flex items-center justify-center p-8 bg-background">
                    <Card className="w-full max-w-md overflow-hidden p-0">
                        <CardContent className="p-6 md:p-8">
                            <form onSubmit={handleSubmit}>
                                <FieldGroup>
                                    <div className="flex flex-col items-center gap-2 text-center">
                                        <h1 className="text-2xl font-bold text-foreground">
                                            Welcome Back
                                        </h1>
                                        <p className="text-muted-foreground text-balance">
                                            Enter your credentials to access
                                            your account
                                        </p>
                                    </div>

                                    {/* Email Field */}
                                    <Field>
                                        <FieldLabel htmlFor="email">
                                            Email
                                        </FieldLabel>
                                        <Input
                                            id="email"
                                            type="email"
                                            placeholder="name@example.com"
                                            value={data.email}
                                            onChange={(e) =>
                                                setData("email", e.target.value)
                                            }
                                            required
                                        />
                                        {errors.email && (
                                            <FieldError className="text-destructive">
                                                {errors.email}
                                            </FieldError>
                                        )}
                                    </Field>

                                    {/* Password Field */}
                                    <Field>
                                        <div className="flex items-center">
                                            <FieldLabel htmlFor="password">
                                                Password
                                            </FieldLabel>
                                            <a
                                                href="#"
                                                className="ml-auto text-sm text-muted-foreground hover:text-foreground underline-offset-2 hover:underline"
                                            >
                                                Forgot your password?
                                            </a>
                                        </div>
                                        <Input
                                            id="password"
                                            type="password"
                                            placeholder="Enter your password"
                                            value={data.password}
                                            onChange={(e) =>
                                                setData(
                                                    "password",
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        {errors.password && (
                                            <FieldError className="text-destructive">
                                                {errors.password}
                                            </FieldError>
                                        )}
                                    </Field>

                                    {/* Remember Me */}
                                    <div className="flex items-center space-x-2">
                                        <input
                                            id="remember"
                                            type="checkbox"
                                            checked={data.remember}
                                            onChange={(e) =>
                                                setData(
                                                    "remember",
                                                    e.target.checked,
                                                )
                                            }
                                            className="h-4 w-4 rounded border-border bg-background text-primary focus:ring-ring focus:ring-offset-0"
                                        />
                                        <FieldLabel
                                            htmlFor="remember"
                                            className="text-sm text-muted-foreground font-normal cursor-pointer"
                                        >
                                            Remember me for 30 days
                                        </FieldLabel>
                                    </div>

                                    {/* Submit Button */}
                                    <Field>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full bg-primary text-primary-foreground hover:bg-primary/90 font-medium cursor-pointer"
                                        >
                                            {processing
                                                ? "Signing in..."
                                                : "Sign in"}
                                        </Button>
                                    </Field>

                                    {/* Help Text */}
                                    <FieldDescription className="text-center text-muted-foreground">
                                        Contact your school administrator for
                                        access
                                    </FieldDescription>
                                </FieldGroup>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
