import * as React from "react"
import { cn } from "@/lib/utils"

const Button = React.forwardRef(({ className, variant = "default", size = "default", ...props }, ref) => {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-950 disabled:pointer-events-none disabled:opacity-50",
        {
          "bg-gray-900 text-gray-50 shadow hover:bg-gray-900/90": variant === "default",
          "bg-red-600 text-white shadow-sm hover:bg-red-600/90": variant === "destructive",
          "border border-gray-200 bg-white shadow-sm hover:bg-gray-100 hover:text-gray-900": variant === "outline",
          "bg-gray-100 text-gray-900 shadow-sm hover:bg-gray-100/80": variant === "secondary",
          "hover:bg-gray-100 hover:text-gray-900": variant === "ghost",
          "text-gray-900 underline-offset-4 hover:underline": variant === "link",
        },
        {
          "h-9 px-4 py-2": size === "default",
          "h-8 rounded-md px-3 text-xs": size === "sm",
          "h-10 rounded-md px-8": size === "lg",
          "h-9 w-9": size === "icon",
        },
        className
      )}
      ref={ref}
      {...props}
    />
  )
})
Button.displayName = "Button"

export { Button }
